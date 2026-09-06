<?php

/**
 * @file
 * The canonical OAF Sentry browser partial (see docs/monitoring.md in the
 * infrastructure repo; change it there first, then update every copy).
 * Extracted into its own file so it can be required independently of
 * utility.php (which is not safe to require in the unit-test suite because
 * it redefines twfy_debug(), already stubbed in tests/bootstrap.php).
 */

use Sentry\SentrySdk;

/**
 * Renders the Sentry browser SDK loader script.
 *
 * Renders only when the server SDK has a DSN - the same off switch as the
 * backend, so browser events land in the same openaustraliaorgau project -
 * and never for crawlers, whose errors we can't act on and which vastly
 * outnumber real people. Deliberately narrow: facebookexternalhit is the
 * crawler, while the Facebook in-app browser is a real person whose errors
 * are worth seeing.
 */
function sentry_browser_script() {
    global $THEUSER;

    if (!defined('SENTRY_DSN') || !SENTRY_DSN || !class_exists('\Sentry\SentrySdk')) {
        return;
    }
    $client = SentrySdk::getCurrentHub()->getClient();
    // The loader script key is the public key from the DSN. The host is
    // region-specific and our org is in Sentry's EU region: the US host
    // (js.sentry-cdn.com) answers 200 with a no-op stub, which would leave
    // every browser signal silently inert.
    $dsn = $client ? $client->getOptions()->getDsn() : null;
    $public_key = $dsn ? $dsn->getPublicKey() : null;
    if (!$public_key) {
        return;
    }
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    if (preg_match('/bot\b|crawler|spider|facebookexternalhit|slurp|archiver|inspectiontool/i', $user_agent)) {
        return;
    }
    $options = $client->getOptions();

    $config = [
        // Match the server's environment and release so browser and server
        // events tie to the same deploy.
        'environment' => $options->getEnvironment(),
        // Sample 10% of pageloads/navigations for browser tracing.
        'tracesSampleRate' => 0.1,
        // Only record session replays when an error occurs, with default
        // privacy masking (maskAllText/blockAllMedia) left enabled.
        'replaysSessionSampleRate' => 0,
        'replaysOnErrorSampleRate' => 1.0,
        // Send user IP with events, matching the backend's send_default_pii.
        'sendDefaultPii' => true,
    ];
    if ($options->getRelease()) {
        $config['release'] = $options->getRelease();
    }
    $config_json = json_encode($config);
    // Errors thrown by browser extensions, in-app webview bridges and email
    // link scanners rather than by our own code. We cannot fix any of these
    // and they drown out real problems. Anything our own JavaScript causes
    // belongs in a fix, not in this list. denyUrls needs regex literals, so
    // it is spliced in below rather than passed through json_encode.
    $ignore_errors = json_encode([
        // Microsoft Outlook and Office scanning links in our alert emails.
        'Object Not Found Matching Id',
        // Android webview tearing down its JavaScript bridge mid-page.
        'Error invoking postMessage: Java object is gone',
        // Cryptocurrency wallet extensions.
        'Failed to connect to MetaMask',
        // Accessibility and screen reader extensions reading their own state.
        'isHighContrast',
        // iOS in-app browsers and extensions expecting their native bridge.
        'window.webkit.messageHandlers',
        // In-app browsers' autofill bridge (e.g. Facebook/Instagram on iOS)
        '_AutofillCallbackHandler',
    ]);
    // Tie browser events to the same user as server events - id only, no
    // email or name, matching the scope set in init.php.
    $set_user_js = '';
    if (isset($THEUSER) && $THEUSER->isloggedin()) {
        $set_user_js = 'Sentry.setUser({ id: ' . json_encode((string) $THEUSER->user_id()) . ' });';
    }

    // Meta tags the browser SDK reads on pageload to continue the backend
    // trace, stitching the browser transaction to the PHP transaction that
    // rendered it. SDK-generated values with no user input.
    echo '<meta name="sentry-trace" content="' . htmlspecialchars(\Sentry\getTraceparent()) . '">' . "\n";
    echo '<meta name="baggage" content="' . htmlspecialchars(\Sentry\getBaggage()) . '">' . "\n";
    // sentryOnLoad must be defined before the loader script so the
    // lazy-loaded SDK picks up our configuration when it initialises.
    ?>
<script>
    window.sentryOnLoad = function () {
        var config = <?php echo $config_json; ?>;
        config.ignoreErrors = <?php echo $ignore_errors; ?>;
        config.denyUrls = [
            /^chrome-extension:\/\//i,
            /^moz-extension:\/\//i,
            /^safari-(web-)?extension:\/\//i
        ];
        Sentry.init(config);
        <?php echo $set_user_js; ?>
    };
</script>
<script src="https://js-de.sentry-cdn.com/<?php echo htmlspecialchars($public_key); ?>.min.js" crossorigin="anonymous"></script>
    <?php
}
