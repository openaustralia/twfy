<?php

/**
 * @file
 * Base test case for page rendering integration tests.
 *
 * Uses proc_open to run pages in a completely separate PHP process,
 * avoiding symbol conflicts between the test bootstrap and the application.
 */

require_once __DIR__ . '/bootstrap.php';

use PHPUnit\Framework\TestCase;

/**
 * Base class for page rendering tests.
 */
abstract class PageRenderingIntegrationTestCase extends TestCase {

    private static bool $createdConfGeneral = false;
    private static ?string $originalConfGeneral = null;

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
        $path = __DIR__ . '/../conf/general';
        if (self::$createdConfGeneral) {
            if (self::$originalConfGeneral !== null) {
                file_put_contents($path, self::$originalConfGeneral);
            } elseif (file_exists($path)) {
                unlink($path);
            }
            self::$createdConfGeneral = false;
            self::$originalConfGeneral = null;
        }
    }

    /**
     * Render a page script in a separate process and assert it produces valid output.
     */
    protected function assertPageRenders(string $script, string $deviceType = 'desktop'): string {
        $config = getTestDbConfig();
        $env = [
            'DB_HOST' => $config['host'],
            'DB_USER' => $config['user'],
            'DB_PASSWORD' => $config['pass'],
            'DB_NAME' => $config['name'],
        ];

        $cmd = sprintf(
            'php -d display_errors=stderr -r %s',
            escapeshellarg(
                '$_SERVER["DEVICE_TYPE"] = ' . var_export($deviceType, true) . ';' .
                '$_SERVER["REQUEST_METHOD"] = "GET";' .
                '$_SERVER["REQUEST_URI"] = "/";' .
                '$_SERVER["HTTP_HOST"] = "localhost";' .
                '$_GET = []; $_POST = [];' .
                'chdir(dirname(' . var_export($script, true) . '));' .
                'include ' . var_export($script, true) . ';'
            )
        );

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($cmd, $descriptors, $pipes, dirname($script), $env + getenv());
        $this->assertIsResource($process);

        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        $this->assertSame(0, $exitCode, "Page rendered with errors: $stderr");
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
            self::$originalConfGeneral = file_get_contents($path);
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
