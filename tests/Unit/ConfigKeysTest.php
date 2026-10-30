<?php
use PHPUnit\Framework\TestCase;

// The config files are built from the two .example files (the Proxmox template copies them, then adds its own settings),
// so a setting the code needs but the example lacks is a setting a new install silently does not have: the code reads
// an undefined key, PHP warns, and the feature misbehaves. Every unguarded read must have a default in the example.
final class ConfigKeysTest extends TestCase
{
    private const ROOT = __DIR__ . '/../../wifidb';

    /** @return string[] the option names an example file defines (live lines only: a commented-out option is not a default) */
    private function defined(string $file): array
    {
        $text = preg_replace('~/\*.*?\*/~s', '', file_get_contents($file));
        preg_match_all("/^\s*'([A-Za-z_0-9]+)'\s*=>/m", $text, $m);
        return array_unique($m[1]);
    }

    /** @return array<string,string> option => first "file:line" that reads it without a guard */
    private function unguardedReads(string $variable): array
    {
        $reads = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveCallbackFilterIterator(
            new RecursiveDirectoryIterator(self::ROOT, FilesystemIterator::SKIP_DOTS),
            fn ($current) => !in_array($current->getFilename(), ['vendor', 'smarty', 'js', 'sql'], true)
        ));
        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') { continue; }
            foreach (file($file->getPathname()) as $number => $line) {
                if (preg_match('~^\s*(\*|//|#|/\*)~', $line)) { continue; }   // a comment that mentions an option is not a read
                if (!preg_match_all('/\$' . $variable . "\[\s*'([A-Za-z_0-9]+)'\s*\]/", $line, $m)) { continue; }
                foreach ($m[1] as $key) {
                    $quoted = preg_quote($key, '/');
                    $guarded = preg_match('/(isset|empty|array_key_exists)\s*\(\s*\$' . $variable . "\[\s*'$quoted'/", $line)    // isset($config['k'])
                        || preg_match('/\$' . $variable . "\[\s*'$quoted'\s*\]\s*\?\?/", $line)                              // $config['k'] ?? default
                        || preg_match('/\$' . $variable . "\[\s*'$quoted'\s*\]\s*=[^=>]/", $line);                          // $config['k'] = ... (a write)
                    if (!$guarded && !isset($reads[$key])) {
                        $reads[$key] = substr($file->getPathname(), strlen(self::ROOT) + 1) . ':' . ($number + 1);
                    }
                }
            }
        }
        return $reads;
    }

    public function testEveryOptionTheSiteReadsHasADefaultInTheExample(): void
    {
        $defined = $this->defined(self::ROOT . '/wifidb/lib/config.inc.php.example');
        $missing = array_diff_key($this->unguardedReads('config'), array_flip($defined));
        $this->assertSame([], $missing, "options read without a default in config.inc.php.example:\n" . print_r($missing, true));
    }

    public function testEveryOptionTheDaemonsReadHasADefaultInTheExample(): void
    {
        $defined = $this->defined(self::ROOT . '/tools/daemon.config.inc.php.example');
        $reads = array_diff_key($this->unguardedReads('daemon_config'), array_flip($defined));
        $this->assertSame([], $reads, "options read without a default in daemon.config.inc.php.example:\n" . print_r($reads, true));
    }

    public function testTheScanReallyFindsReads(): void
    {
        // guards against the scan quietly matching nothing, which would make the two tests above pass for the wrong reason
        $reads = $this->unguardedReads('config');
        $this->assertArrayHasKey('hosturl', $reads);
        $this->assertGreaterThan(40, count($reads));
        $this->assertContains('hosturl', $this->defined(self::ROOT . '/wifidb/lib/config.inc.php.example'));
        $this->assertNotContains('signup_smtp_host', $this->defined(self::ROOT . '/wifidb/lib/config.inc.php.example'), 'a commented-out option is not a default');
    }
}
