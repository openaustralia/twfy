<?php

/**
 * @file
 * Base test case for page rendering integration tests.
 *
 * Uses proc_open to run pages in a completely separate PHP process,
 * avoiding symbol conflicts between the test bootstrap and the application.
 */

require_once __DIR__ . '/bootstrap.php';

use Illuminate\Cookie\CookieValuePrefix;
use OpenAustralia\TWFY\Models\Session;
use OpenAustralia\TWFY\Models\User;
use PHPUnit\Framework\TestCase;

/**
 * Base class for page rendering tests.
 */
abstract class PageRenderingIntegrationTestCase extends TestCase {

    private static bool $createdConfGeneral = false;
    private static ?string $originalConfGeneral = null;

    /**
     * The sessions signedInCookies() saved, removed again after each test.
     *
     * @var list<string>
     */
    private array $sessionIds = [];

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
    protected function tearDown(): void {
        Session::whereIn('id', $this->sessionIds)->delete();
        $this->sessionIds = [];
        parent::tearDown();
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
     * Run a page script in a separate process with the given request method
     * and query string, and report what it did.
     *
     * Unlike assertPageRenders() this makes no assertions about the output, so
     * it suits error pages and non-GET requests. The page runs against the
     * committed database, not any transaction held by the test process.
     *
     * @param string $script
     *   The page script.
     * @param string $method
     *   The request method.
     * @param array $query
     *   The query string variables, as $_GET.
     * @param array $post
     *   The form variables, as $_POST.
     * @param array $cookies
     *   The cookies, as $_COOKIE.
     *
     * @return array
     *   Keys output, stderr, status (the HTTP response code the page set) and
     *   exit.
     */
    protected function renderPage(string $script, string $method = 'GET', array $query = [], array $post = [], array $cookies = []): array {
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
                '$_SERVER["DEVICE_TYPE"] = "desktop";' .
                '$_SERVER["REQUEST_METHOD"] = ' . var_export($method, true) . ';' .
                '$_SERVER["REQUEST_URI"] = "/";' .
                '$_SERVER["HTTP_HOST"] = "localhost";' .
                '$_GET = ' . var_export($query, true) . '; $_POST = ' . var_export($post, true) . '; $_COOKIE = ' . var_export($cookies, true) . ';' .
                'register_shutdown_function(function () { fwrite(STDERR, "\nSTATUS=" . (http_response_code() ?: 200) . "\n"); });' .
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

        preg_match('/STATUS=(\d+)/', $stderr, $matches);

        return [
            'output' => $output,
            'stderr' => $stderr,
            'status' => isset($matches[1]) ? (int) $matches[1] : null,
            'exit' => $exitCode,
        ];
    }

    /**
     * The cookies of a browser that Laravel has signed in as a user.
     *
     * The page runs in another process, so the session is saved to the committed database and removed after the
     * test.
     *
     * @return array<string, string>
     *   The encrypted session cookie, by name.
     */
    protected function signedInCookies(User $user): array {
        $app = twfy_laravel();
        $encrypter = $app->make('encrypter');
        $name = $app->make('config')->get('session.cookie');
        $id = bin2hex(random_bytes(20));
        Session::create([
            'id' => $id,
            'user_id' => $user->user_id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'page rendering spec',
            'payload' => base64_encode(serialize(['_token' => bin2hex(random_bytes(20)), twfy_laravel_guard()->getName() => $user->user_id])),
            'last_activity' => time(),
        ]);
        $this->sessionIds[] = $id;
        return [$name => $encrypter->encrypt(CookieValuePrefix::create($name, $encrypter->getKey()) . $id, false)];
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
            . 'define("RECESSFILE", "/dev/null");' . "\n"
            . 'define("APP_KEY", ' . var_export(APP_KEY, true) . ');' . "\n";

        file_put_contents($path, $content);
        self::$createdConfGeneral = true;
    }

}
