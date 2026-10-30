<?php
// Small standalone helpers used by init.inc.php. No config or database needed, so they can be unit tested.
// The theme name comes from a cookie and ends up in template/compile paths, so only accept an existing folder under themes/.
if(!function_exists('wifidb_resolve_theme')) {
	function wifidb_resolve_theme($requested, $install, $default)
	{
		if(is_string($requested) && preg_match('/^[A-Za-z0-9_-]+$/', $requested) && is_dir($install.'themes/'.$requested))
		{
			return $requested;
		}
		return $default;
	}
}
// Replacement for the FILTER_SANITIZE_STRING filter, which is deprecated in PHP 8.1. Same result: tags stripped, quotes encoded.
if(!function_exists('wifidb_sanitize_string')) {
	function wifidb_sanitize_string($value)
	{
		return str_replace(array("'", '"'), array('&#39;', '&#34;'), strip_tags((string)$value));
	}
	function wifidb_input_string($type, $name)
	{
		$value = filter_input($type, $name, FILTER_UNSAFE_RAW);
		return is_string($value) ? wifidb_sanitize_string($value) : $value;
	}
}

// Where to send someone back to after login or an action: a path on this site, or an address on this site's own host.
// Anything else (another site, "//evil.example", javascript:, control characters) gives $default.
if(!function_exists('wifidb_safe_return')) {
	function wifidb_safe_return($value, $siteHost, $default = '')
	{
		if(!is_string($value)) { return $default; }
		if(preg_match('/[\x00-\x1f\x7f]/', $value) || strpos($value, chr(92)) !== false) { return $default; }
		$value = trim($value);
		if($value === '') { return $default; }
		if($value[0] === '/')
		{
			// "//host" means another site to a browser
			return (isset($value[1]) && $value[1] === '/') ? $default : $value;
		}
		if(preg_match('#^https?://#i', $value))
		{
			$host = parse_url($value, PHP_URL_HOST);
			if(is_string($host) && $siteHost !== '' && strcasecmp($host, $siteHost) === 0) { return $value; }
		}
		return $default;
	}
}

// A value as a JavaScript string literal (quotes included) that is safe inside an inline <script> block.
if(!function_exists('wifidb_js_string')) {
	function wifidb_js_string($value)
	{
		return json_encode((string)$value, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
	}
}

// A short-lived database connection from the web site's config, for small standalone pages (/health/ready, the signal
// graph) that must not load the whole application. $timeout bounds connecting to the server.
if(!function_exists('wifidb_pdo_from_config')) {
	function wifidb_pdo_from_config($config, $timeout = 2)
	{
		if($config['srvc'] === 'sqlsrv')
		{
			$dsn = 'sqlsrv:Server=' . $config['host'] . ',' . (int)$config['port'] . ';Database=' . $config['db'] . ';TrustServerCertificate=true;LoginTimeout=' . (int)$timeout;
			$options = array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION);
		}
		else
		{
			$dsn = 'mysql:host=' . $config['host'] . ';port=' . (int)$config['port'] . ';dbname=' . $config['db'] . ';charset=' . $config['charset'];
			$options = array(PDO::ATTR_TIMEOUT => (int)$timeout, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION);
		}
		return new PDO($dsn, $config['db_user'], $config['db_pwd'], $options);
	}
}
