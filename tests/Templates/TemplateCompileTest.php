<?php
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;


// Compiling a template needs no data, so this catches syntax errors and unknown tags/modifiers
// in every theme without running the application.
final class TemplateCompileTest extends TestCase
{
    private static string $compileDir;

    public static function setUpBeforeClass(): void
    {
        self::$compileDir = sys_get_temp_dir() . '/wifidb-tpl-' . getmypid();
        mkdir(self::$compileDir, 0777, true);
    }

    public static function tearDownAfterClass(): void
    {
        foreach (glob(self::$compileDir . '/*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir(self::$compileDir);
    }

    public static function templates(): array
    {
        $cases = [];
        foreach (glob(WIFIDB_WEB . '/themes/*/templates', GLOB_ONLYDIR) as $dir) {
            $theme = basename(dirname($dir));
            foreach (glob($dir . '/*.tpl') as $file) {
                $cases["$theme/" . basename($file)] = [$dir, basename($file)];
            }
        }
        return $cases;
    }

    #[DataProvider('templates')]
    public function testTemplateCompiles(string $templateDir, string $name): void
    {
        $smarty = new Smarty();
        $smarty->setTemplateDir($templateDir);
        $smarty->setCompileDir(self::$compileDir);
        $smarty->setCaching(Smarty::CACHING_OFF);
        $smarty->muteUndefinedOrNullWarnings();

        $template = $smarty->createTemplate($name);
        $template->compileTemplateSource();
        $this->assertFileExists($template->compiled->filepath, "$name did not compile");
    }

    public function testBothThemesAreCovered(): void
    {
        $themes = array_unique(array_map(fn ($case) => basename(dirname($case[0])), self::templates()));
        sort($themes);
        $this->assertSame(['vistumbler_classic', 'vistumbler_mobile'], $themes);
    }
}
