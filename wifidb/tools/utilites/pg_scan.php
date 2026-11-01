<?php
/*
pg_scan.php, lists the SQL queries that PostgreSQL would reject because they have no pgsql version: a query naming a
mixed-case column or table (AP_ID, wGPS.Lat, ... As Lat) unquoted, which PostgreSQL folds to lower case, and that isn't
in, or next to, a branch for pgsql. The mixed-case names are read from blank_db.pgsql, so the list follows the schema.

Unlike pg_audit.py it reads PHP's own tokens: a query split over several lines is one statement, a name after a table
alias (wap.AP_ID) counts, and "next to a pgsql branch" means an arm of the same if/elseif chain (or a ternary on the
service), not just a line nearby.

  php pg_scan.php [WIFIDB_DIR]     (default: the folder above tools/)
*/

$root = rtrim(isset($argv[1]) ? $argv[1] : dirname(__FILE__).'/../..', '/');
$schema = @file_get_contents("$root/blank_db.pgsql");
if($schema === false) { fwrite(STDERR, "No blank_db.pgsql in $root\n"); exit(2); }
preg_match_all('/"([A-Za-z_][A-Za-z0-9_]*)"/', $schema, $m);
$mixed = array();
foreach(array_unique($m[1]) as $name) { if(preg_match('/[A-Z]/', $name) && strpos($name, '$') === false) { $mixed[$name] = true; } }
$mixedRe = '/(?<![A-Za-z0-9_"])('.implode('|', array_map('preg_quote', array_keys($mixed))).')(?![A-Za-z0-9_"])/';

/** The SQL a statement's strings spell, with quoted identifiers and SQL string values taken out. */
function sql_text(array $strings)
{
	$text = '';
	foreach($strings as $s)
	{
		$raw = $s;
		if($raw !== '' && ($raw[0] === '"' || $raw[0] === "'")) { $raw = substr($raw, 1, -1); }
		$raw = str_replace(array('\\"', "\\'"), array('"', "'"), $raw);
		$text .= ' '.$raw;
	}
	$text = preg_replace('/\{\$[^}]*\}|\$[A-Za-z_][A-Za-z0-9_\->\[\]\']*/', ' ', $text);   # PHP variables in the string
	$text = preg_replace('/"[^"]*"/', ' ', $text);                                         # quoted identifiers
	$text = preg_replace("/'[^']*'/", ' ', $text);                                         # SQL string values
	$text = preg_replace('/:[A-Za-z_][A-Za-z0-9_]*/', ' ', $text);                         # named parameters (:BSSID)
	$text = preg_replace('#/\*.*?\*/#s', ' ', $text);                                      # SQL comments
	return $text;
}

$found = 0; $files = 0;
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
$paths = array();
foreach($it as $f) { $p = str_replace('\\', '/', $f->getPathname()); if(substr($p, -4) === '.php' && !preg_match('#/(vendor|smarty|themes|out)/#', $p) && basename($p) !== 'ver.php') { $paths[] = $p; } }
sort($paths);
foreach($paths as $path)
{
	$tokens = token_get_all(file_get_contents($path));
	$stack = array();          # open blocks: [cond, chain]
	$chainAt = array();        # depth => the if/elseif chain last closed at that depth
	$pending = null;           # an if/elseif/else waiting for its '{' (or its single statement)
	$stmt = array('strings' => array(), 'text' => '', 'line' => 0);
	$report = array();
	$n = count($tokens);
	$flush = function () use (&$stmt, &$stack, &$report, $mixedRe) {
		if($stmt['strings'])
		{
			$sql = sql_text($stmt['strings']);
			# the shape of a query, not just a keyword ("Update profile Vis_ver for ..." is a log message)
			if(preg_match('/\bSELECT\b.+\bFROM\b|\bINSERT\s+INTO\b|\bUPDATE\s+\S+\s+SET\b|\bDELETE\s+FROM\b|\bMERGE\b/is', $sql) && preg_match_all($mixedRe, $sql, $hits))
			{
				# judged at the end of the file: a pgsql arm later in the same if/elseif chain hasn't been read yet
				$report[] = array($stmt['line'], implode(',', array_unique($hits[1])), stripos($stmt['text'], 'pgsql') !== false, $stack);
			}
		}
		$stmt = array('strings' => array(), 'text' => '', 'line' => 0);
	};
	for($i = 0; $i < $n; $i++)
	{
		$t = $tokens[$i];
		$id = is_array($t) ? $t[0] : null;
		$s = is_array($t) ? $t[1] : $t;
		if($id === T_IF || $id === T_ELSEIF || $id === T_ELSE)
		{
			$flush();
			$isElse = ($id === T_ELSE);
			# "else if": the T_IF that follows belongs to the same chain
			if($isElse)
			{
				$j = $i + 1; while($j < $n && is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) { $j++; }
				if($j < $n && is_array($tokens[$j]) && $tokens[$j][0] === T_IF) { $i = $j; $id = T_ELSEIF; $isElse = false; }
			}
			$cond = 'else';
			if(!$isElse)
			{
				# the condition: the balanced (...) after the keyword
				$j = $i + 1; while($j < $n && $tokens[$j] !== '(') { $j++; }
				$depth = 0; $cond = '';
				for(; $j < $n; $j++)
				{
					$c = is_array($tokens[$j]) ? $tokens[$j][1] : $tokens[$j];
					if($tokens[$j] === '(') { $depth++; }
					if($tokens[$j] === ')') { $depth--; }
					$cond .= $c;
					if($depth === 0) { break; }
				}
				$i = $j;
			}
			$d = count($stack);
			if($id === T_IF) { $chain = new stdClass(); $chain->conds = array($cond); }
			else { $chain = isset($chainAt[$d]) ? $chainAt[$d] : (function () { $c = new stdClass(); $c->conds = array(); return $c; })(); $chain->conds[] = $cond; }
			$pending = array('cond' => $cond, 'chain' => $chain);
			# no '{' next: the arm is the one statement that follows
			$j = $i + 1; while($j < $n && is_array($tokens[$j]) && in_array($tokens[$j][0], array(T_WHITESPACE, T_COMMENT), true)) { $j++; }
			if($j < $n && $tokens[$j] !== '{' && $tokens[$j] !== ':')
			{
				$stack[] = array('cond' => $cond, 'chain' => $chain, 'single' => true);
				$pending = null;
			}
			continue;
		}
		if($s === '{' || $id === T_CURLY_OPEN || $id === T_DOLLAR_OPEN_CURLY_BRACES)
		{
			if($id === T_CURLY_OPEN || $id === T_DOLLAR_OPEN_CURLY_BRACES) { $stack[] = array('cond' => null, 'chain' => null, 'interp' => true); $stmt['text'] .= $s; continue; }
			$flush();
			if($pending) { $stack[] = $pending + array('single' => false); $pending = null; }
			else { $stack[] = array('cond' => null, 'chain' => null); }
			continue;
		}
		if($s === '}')
		{
			$top = array_pop($stack);
			if($top && !empty($top['interp'])) { $stmt['text'] .= $s; continue; }
			$flush();
			if($top && $top['chain']) { $chainAt[count($stack)] = $top['chain']; }
			continue;
		}
		if($s === ';')
		{
			$flush();
			# a brace-less arm ends with its statement
			while($stack && !empty(end($stack)['single'])) { $top = array_pop($stack); $chainAt[count($stack)] = $top['chain']; }
			continue;
		}
		if($id === T_CONSTANT_ENCAPSED_STRING || $id === T_ENCAPSED_AND_WHITESPACE)
		{
			# a name handed to a function, ident('Lat'), is quoted by it: not query text
			$prev = $i - 1; while($prev >= 0 && is_array($tokens[$prev]) && $tokens[$prev][0] === T_WHITESPACE) { $prev--; }
			$next = $i + 1; while($next < $n && is_array($tokens[$next]) && $tokens[$next][0] === T_WHITESPACE) { $next++; }
			if($prev >= 0 && $tokens[$prev] === '(' && $next < $n && ($tokens[$next] === ')' || $tokens[$next] === ',') && preg_match('/^([\'"])[A-Za-z_][A-Za-z0-9_]*\1$/', $s))
			{
				$stmt['text'] .= $s;
				continue;
			}
			if(!$stmt['strings']) { $stmt['line'] = $t[2]; }
			$stmt['strings'][] = $s;
		}
		$stmt['text'] .= $s;
	}
	$flush();
	$kept = array();
	foreach($report as $r)
	{
		if($r[2]) { continue; }   # the statement itself picks by service (a ?: on pgsql)
		$keep = true;
		foreach($r[3] as $frame)
		{
			if($frame['cond'] === null) { continue; }
			# in the pgsql branch itself: a mixed-case name there was left unquoted
			if(preg_match('/==\s*[\'"]pgsql[\'"]/', $frame['cond'])) { $r[1] .= '  (unquoted, in the pgsql branch)'; break; }
			# an arm (the mysql one, the sqlsrv one, or the else) of a chain that has a pgsql arm
			if(stripos($frame['cond'], 'pgsql') !== false || (!empty($frame['chain']) && stripos(implode(' ', $frame['chain']->conds), 'pgsql') !== false)) { $keep = false; break; }
		}
		if($keep) { $kept[] = $r; }
	}
	$report = $kept;
	if($report)
	{
		$files++;
		$rel = substr($path, strlen($root) + 1);
		echo "=== $rel (".count($report).")\n";
		foreach($report as $r) { printf("  %5d  %s\n", $r[0], $r[1]); $found++; }
	}
}
echo "\n$found queries in $files files have mixed-case names and no pgsql version.\n";
exit($found ? 1 : 0);
