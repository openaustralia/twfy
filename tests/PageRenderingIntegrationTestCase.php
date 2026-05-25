<?php

/**
 * @file
 * Base test case for page rendering integration tests using process isolation.
 */

require_once __DIR__ . '/bootstrap.php';

use PHPUnit\Framework\TestCase;

/**
 * Base class for page rendering tests.
 *
 * Subclasses use #[RunInSeparateProcess] to avoid symbol conflicts
 * between the test bootstrap and the application's init.php.
 */
abstract class PageRenderingIntegrationTestCase extends TestCase {

    private static bool $createdConfGeneral = false;

    /**
     *
     */
    public static function setUpBeforeClass(): void {
        $conn = getSharedTestConnection();
        if (!$conn) {
            self::markTestSkipped('Database connection not available');
        }
        self::ensureConfGeneral();
    }

    /**
     *
     */
    protected function setUp(): void {
        $conn = getSharedTestConnection();
        if (!$conn) {
            $this->markTestSkipped('Database connection not available');
        }
    }

    /**
     *
     */
    public static function tearDownAfterClass(): void {
        if (self::$createdConfGeneral) {
            $path = __DIR__ . '/../conf/general';
            if (file_exists($path)) {
                unlink($path);
            }
            self::$createdConfGeneral = false;
        }
    }

    /**
     * Render a page script and assert it produces valid output.
     */
    protected function assertPageRenders(string $script, string $deviceType = 'desktop'): string {
        $_SERVER['DEVICE_TYPE'] = $deviceType;
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_GET = [];
        $_POST = [];

        ob_start();
        include $script;
        $output = ob_get_clean();

        $this->assertNotEmpty($output);
        $this->assertStringContainsString('<html', $output);
        $this->assertStringContainsString('OpenAustralia', $output);

        return $output;
    }

    /**
     * Ensure conf/general exists so init.php can load.
     */
    private static function ensureConfGeneral(): void {
        $path = __DIR__ . '/../conf/general';
        if (file_exists($path)) {
            return;
        }

        $config = getTestDbConfig();
        if ($config === null) {
            return;
        }

        $content = '<?php' . "\n"
            . 'define("DB_HOST", ' . var_export($config['host'], true) . ');' . "\n"
            . 'define("DB_USER", ' . var_export($config['user'], true) . ');' . "\n"
            . 'define("DB_PASSWORD", ' . var_export($config['pass'], true) . ');' . "\n"
            . 'define("DB_NAME", ' . var_export($config['name'], true) . ');' . "\n"
            . 'define("DOMAIN", "localhost");' . "\n"
            . 'define("COOKIEDOMAIN", "localhost");' . "\n"
            . 'define("CONTACTEMAIL", "test@example.com");' . "\n"
            . 'define("BASEDIR", ' . var_export(__DIR__ . '/../www/docs/', true) . ');' . "\n"
            . 'define("WEBPATH", "/");' . "\n"
            . 'define("DEVSITE", true);' . "\n"
            . 'define("DEBUGTAG", "debug");' . "\n"
            . 'define("TIMEZONE", "Australia/Sydney");' . "\n"
            . 'define("RAWDATA", "/tmp/pwdata/");' . "\n"
            . 'define("PWMEMBERS", "/tmp/pwdata/members/");' . "\n"
            . 'define("DBBACKUP", "/tmp/backup/");' . "\n"
            . 'define("INCLUDESPATH", ' . var_export(__DIR__ . '/../www/includes/', true) . ');' . "\n"
            . 'define("IMAGEPATH", "/images/");' . "\n"
            . 'define("FILEIMAGEPATH", ' . var_export(__DIR__ . '/../www/docs/images/', true) . ');' . "\n"
            . 'define("REGMEMPDFPATH", "regmem/scan/");' . "\n"
            . 'define("METADATAPATH", ' . var_export(__DIR__ . '/../www/includes/easyparliament/metadata.php', true) . ');' . "\n"
            . 'define("XAPIANDB", "/tmp/searchdb");' . "\n"
            . 'define("RECESSFILE", "/dev/null");' . "\n";

        file_put_contents($path, $content);
        self::$createdConfGeneral = true;
    }

}
