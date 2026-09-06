<?php

/**
 * @file
 */

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/bootstrap.php';

// SentryBrowserScript.php is safe to require here - unlike utility.php it
// doesn't redefine twfy_debug() (already stubbed in bootstrap.php). It uses
// the real Sentry SDK (sentry/sentry 4.x) which composer installs; Sentry\init()
// inside individual test methods provides a live client so the function can
// exercise its full output path.
require_once __DIR__ . '/../www/includes/SentryBrowserScript.php';

/**
 * sentry_browser_script() renders the Sentry browser loader only when the
 * server-side SDK is configured, no bot is detected, and the DSN has a
 * public key. All early-return paths and the happy path are covered here so
 * SonarCloud's new-code coverage gate (≥ 80%) is met.
 *
 * SENTRY_DSN must be defined before the first test that exercises the
 * SDK-initialised path; once defined, PHP constants cannot be redefined or
 * unset, so define() is called unconditionally here in the class body rather
 * than conditionally inside individual tests (which would make the test suite
 * order-dependent).
 */
class SentryBrowserScriptTest extends TestCase {

    /**
     * Sentry's hub is a static singleton; reset it between tests so one
     * test's \Sentry\init() doesn't bleed into the next.
     */
    protected function setUp(): void {
        parent::setUp();
        \Sentry\SentrySdk::setCurrentHub(new \Sentry\State\Hub());
    }

    protected function tearDown(): void {
        parent::tearDown();
        \Sentry\SentrySdk::setCurrentHub(new \Sentry\State\Hub());
        unset($_SERVER['HTTP_USER_AGENT']);
        unset($GLOBALS['THEUSER']);
    }

    /**
     * When SENTRY_DSN is not defined the function exits immediately.
     * (The constant is never defined in the unit test suite, so this is the
     * default state for the first test.)
     */
    public function test_produces_no_output_when_sentry_dsn_is_not_defined() {
        ob_start();
        sentry_browser_script();
        $output = ob_get_clean();

        $this->assertSame('', $output);
    }

    /**
     * When SENTRY_DSN is defined but Sentry has no client (SDK not
     * initialised) the function exits without output.
     */
    public function test_produces_no_output_when_sdk_has_no_client() {
        if (!defined('SENTRY_DSN')) {
            define('SENTRY_DSN', 'https://pub@o0.ingest.sentry.io/1');
        }

        ob_start();
        sentry_browser_script();
        $output = ob_get_clean();

        $this->assertSame('', $output);
    }

    /**
     * Bot user agents are silently dropped - no output.
     */
    public function test_produces_no_output_for_bot_user_agents() {
        \Sentry\init(['dsn' => 'https://pubkey123@o99.ingest.sentry.io/1']);
        $_SERVER['HTTP_USER_AGENT'] = 'Googlebot/2.1 (+http://www.google.com/bot.html)';

        ob_start();
        sentry_browser_script();
        $output = ob_get_clean();

        $this->assertSame('', $output);
    }

    /**
     * A real browser request with the SDK initialised renders the loader
     * script and the sentry-trace/baggage meta tags.
     */
    public function test_renders_loader_script_for_real_browser() {
        \Sentry\init(['dsn' => 'https://pubkey123@o99.ingest.sentry.io/1']);
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';

        ob_start();
        sentry_browser_script();
        $output = ob_get_clean();

        $this->assertStringContainsString(
            'https://js-de.sentry-cdn.com/pubkey123.min.js',
            $output
        );
        $this->assertStringContainsString('sentry-trace', $output);
        $this->assertStringContainsString('baggage', $output);
        $this->assertStringContainsString('window.sentryOnLoad', $output);
    }

    /**
     * The rendered config includes the SDK's environment.
     */
    public function test_rendered_config_includes_environment() {
        \Sentry\init(['dsn' => 'https://pubkey123@o99.ingest.sentry.io/1', 'environment' => 'production']);
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)';

        ob_start();
        sentry_browser_script();
        $output = ob_get_clean();

        $this->assertStringContainsString('"environment":"production"', $output);
    }

    /**
     * When a release is configured it appears in the rendered config.
     */
    public function test_rendered_config_includes_release_when_set() {
        \Sentry\init(['dsn' => 'https://pubkey123@o99.ingest.sentry.io/1', 'release' => 'abc123def456']);
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)';

        ob_start();
        sentry_browser_script();
        $output = ob_get_clean();

        $this->assertStringContainsString('"release":"abc123def456"', $output);
    }

    /**
     * When no release is configured the release key is absent from the output.
     */
    public function test_rendered_config_omits_release_when_not_set() {
        \Sentry\init(['dsn' => 'https://pubkey123@o99.ingest.sentry.io/1']);
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)';

        ob_start();
        sentry_browser_script();
        $output = ob_get_clean();

        $this->assertStringNotContainsString('"release"', $output);
    }

    /**
     * When a user is logged in their id is attached via Sentry.setUser().
     * An anonymous class is used instead of a PHPUnit mock because USER
     * defines isloggedin() below its constructor auto-instantiation point,
     * making it invisible to the mock generator.
     */
    public function test_sets_user_id_when_user_is_logged_in() {
        \Sentry\init(['dsn' => 'https://pubkey123@o99.ingest.sentry.io/1']);
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)';

        $GLOBALS['THEUSER'] = new class {

            public function isloggedin(): bool {
                return true;
            }

            public function user_id(): int {
                return 42;
            }

        };

        ob_start();
        sentry_browser_script();
        $output = ob_get_clean();

        $this->assertStringContainsString('Sentry.setUser({ id: "42" })', $output);
    }

    /**
     * When the user is not logged in no Sentry.setUser() call is emitted.
     */
    public function test_does_not_set_user_when_not_logged_in() {
        \Sentry\init(['dsn' => 'https://pubkey123@o99.ingest.sentry.io/1']);
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)';

        $GLOBALS['THEUSER'] = new class {

            public function isloggedin(): bool {
                return false;
            }

        };

        ob_start();
        sentry_browser_script();
        $output = ob_get_clean();

        $this->assertStringNotContainsString('Sentry.setUser', $output);
    }

    /**
     * The facebookexternalhit crawler is blocked; the Facebook in-app
     * browser (a real person) is not.
     */
    public function test_blocks_facebookexternalhit_but_not_facebook_in_app_browser() {
        \Sentry\init(['dsn' => 'https://pubkey123@o99.ingest.sentry.io/1']);

        $_SERVER['HTTP_USER_AGENT'] = 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)';
        ob_start();
        sentry_browser_script();
        $crawler_output = ob_get_clean();

        // Reset hub so the second Sentry\init() gets a fresh client.
        \Sentry\SentrySdk::setCurrentHub(new \Sentry\State\Hub());
        \Sentry\init(['dsn' => 'https://pubkey123@o99.ingest.sentry.io/1']);
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148 [FBAN/FBIOS;FBDV/iPhone14,2]';
        ob_start();
        sentry_browser_script();
        $fban_output = ob_get_clean();

        $this->assertSame('', $crawler_output, 'facebookexternalhit should be blocked');
        $this->assertStringContainsString('js-de.sentry-cdn.com', $fban_output, 'Facebook in-app browser should not be blocked');
    }

    /**
     * The public key from the DSN is used as the loader script filename.
     */
    public function test_loader_url_uses_dsn_public_key() {
        \Sentry\init(['dsn' => 'https://myPublicKey456@o99.ingest.sentry.io/1']);
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)';

        ob_start();
        sentry_browser_script();
        $output = ob_get_clean();

        $this->assertStringContainsString('myPublicKey456.min.js', $output);
    }

}
