<?php

/**
 * @file
 */

include_once __DIR__ . '/../../includes/easyparliament/init.php';
include_once __DIR__ . '/../../includes/postcode.php';

include_once 'api_functions.php';
$methods = api_get_methods();

// @todo: Need to override error handling.

if ($q_method = get_http_var('method')) {
    if (get_http_var('docs')) {
        $key = 'DOCS';
    } else {
        if (!get_http_var('key')) {
            api_error('No API key provided. Please see http://www.openaustralia.org/api/key for more information.');
            exit;
        }
        $key = get_http_var('key');
        if ($key && !api_check_key($key)) {
            api_error('Invalid API key.');
            exit;
        }
    }
    $match = 0;
    $matched_method = '';
    foreach ($methods as $method => $data) {
        if (strtolower($q_method) == strtolower($method)) {
            $matched_method = $method;
            api_log_call($key);
            $match++;
            if (get_http_var('docs')) {
                $_GET['verbose'] = 1;
                ob_start();
            }
            foreach ($data['parameters'] as $parameter) {
                if ($q_param = trim(get_http_var($parameter))) {
                    $match++;
                    include_once 'api_' . $method . '.php';
                    api_call_user_func_or_error('api_' . $method . '_' . $parameter, [$q_param], 'API call not yet functional', 'api');
                    break;
                }
            }
            if ($match == 1 && (get_http_var('output') || !get_http_var('docs'))) {
                if ($data['required']) {
                    api_error('No parameter provided to function "' .
                        htmlspecialchars($q_method) .
                        '". Possible choices are: ' .
                        implode(', ', $data['parameters']));
                } else {
                    include_once 'api_' . $method . '.php';
                    api_call_user_func_or_error('api_' . $method, [], 'API call not yet functional', 'api');
                    break;
                }
            }
            break;
        }
    }
    if (!$match) {
        api_log_call($key);
        api_front_page('Unknown function "' . htmlspecialchars($q_method) .
            '". Possible functions are: ' .
            implode(', ', array_keys($methods)));
    } else {
        if (get_http_var('docs')) {
            $explorer = ob_get_clean();
            api_documentation_front($matched_method, $explorer);
        }
    }
} else {
    api_front_page();
}

/**
 *
 */
function api_documentation_front($method, $explorer) {
    global $PAGE, $this_page, $DATA;
    $methods = api_get_methods();
    $this_page = 'api_doc_front';
    $DATA->set_page_metadata($this_page, 'title', "$method function");
    $PAGE->page_start();
    $PAGE->stripe_start();
    include_once 'api_' . $method . '.php';
    print '<p align="center"><strong>http://www.openaustralia.org/api/' . $method . '</strong></p>';
    api_call_user_func_or_error('api_' . $method . '_front', [], 'No documentation yet', 'html');
    ?>
    <h4 class="mt-8 text-xl font-semibold text-oa-heading">Explorer</h4>
    <p class="mt-1 text-sm text-oa-subheading">Try out this function without writing any code.</p>
    <div class="mt-4 px-2 md:px-4">
    <form method="get" action="?#output" class="rounded-xl border border-oa-border bg-oa-paper p-6 shadow-sm">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
            <?php foreach ($methods[$method]['parameters'] as $parameter) {
                $value = get_http_var($parameter);
                ?>
                <label for="param_<?php print htmlspecialchars($parameter); ?>" class="block text-sm font-medium text-oa-brand">
                    <?php print htmlspecialchars($parameter); ?>
                </label>
                <input
                    id="param_<?php print htmlspecialchars($parameter); ?>"
                    type="text"
                    name="<?php print htmlspecialchars($parameter); ?>"
                    value="<?php print htmlspecialchars((string) $value); ?>"
                    class="w-full rounded-md border border-oa-border bg-white px-3 py-2 text-sm text-oa-brand shadow-sm outline-none focus:border-oa-heading focus:ring-2 focus:ring-oa-accent/40"
                >
            <?php } ?>
        </div>

        <fieldset class="mt-5">
            <legend class="text-sm font-medium text-oa-brand">Output format</legend>
            <div class="mt-2 flex flex-wrap items-center gap-4 text-sm text-oa-brand">
                <label for="output_js" class="inline-flex items-center gap-2">
                    <input id="output_js" type="radio" name="output" value="js" <?php if (get_http_var('output') == 'js' || !get_http_var('output')) {
                        print ' checked';
                   } ?>>
                    <span>JS</span>
                </label>
                <label for="output_xml" class="inline-flex items-center gap-2">
                    <input id="output_xml" type="radio" name="output" value="xml" <?php if (get_http_var('output') == 'xml') {
                        print ' checked';
                   } ?>>
                    <span>XML</span>
                </label>
                <label for="output_php" class="inline-flex items-center gap-2">
                    <input id="output_php" type="radio" name="output" value="php" <?php if (get_http_var('output') == 'php') {
                        print ' checked';
                   } ?>>
                    <span>Serialised PHP</span>
                </label>
                <label for="output_rabx" class="inline-flex items-center gap-2">
                    <input id="output_rabx" type="radio" name="output" value="rabx" <?php if (get_http_var('output') == 'rabx') {
                        print ' checked';
                   } ?>>
                    <span>RABX</span>
                </label>
            </div>
        </fieldset>

        <div class="mt-6">
            <button type="submit" class="rounded-md bg-oa-brand px-4 py-2 text-sm font-semibold text-white shadow hover:bg-oa-heading focus:outline-none focus:ring-2 focus:ring-oa-accent/60">
                Run Request
            </button>
        </div>
    </form>
    </div>
    <?php
    if ($explorer) {
        $qs = [];
        foreach ($methods[$method]['parameters'] as $parameter) {
            if (get_http_var($parameter)) {
                $qs[] = htmlspecialchars(rawurlencode($parameter) . '=' . urlencode(get_http_var($parameter)));
            }
        }
        print '<h4 class="mt-8 text-xl font-semibold text-oa-heading"><a name="output"></a>Output</h4>';
        print '<p class="mt-2 text-sm text-oa-subheading">URL for this request:</p>';
        print '<p class="mt-1 rounded-md bg-oa-panel px-3 py-2 text-sm text-oa-brand"><strong class="break-all">http://www.openaustralia.org/api/';
        print $method . '?' . implode('&amp;', $qs) . '&amp;output=' . get_http_var('output') . '</strong></p>';
        print '<pre class="mt-3 max-h-[32rem] overflow-auto rounded-xl border border-oa-border bg-oa-brand p-4 text-xs text-white">' . htmlspecialchars($explorer) . '</pre>';
    }
    $sidebar = api_sidebar();
    $PAGE->stripe_end([$sidebar]);
    $PAGE->page_end();
}

/**
 *
 */
function api_front_page($error = '') {
    global $PAGE, $this_page, $THEUSER;
    $methods = api_get_methods();
    $this_page = 'api_front';
    $PAGE->page_start();
    $PAGE->stripe_start();
    if ($error) {
        print "<p style='color: #cc0000'>$error</p>";
    }
    ?>

    <div class="rounded-2xl border border-oa-border bg-gradient-to-br from-oa-paper to-white p-6 shadow-sm">
        <p class="text-oa-brand">Welcome to OpenAustralia's API section, where you can learn how to query our database for information.</p>

        <h3 class="mt-6 text-xl font-semibold text-oa-heading">Overview</h3>

        <ol class="mt-3 list-decimal space-y-3 pl-6 text-base text-oa-brand">
            <li>
                <?php if ($THEUSER->loggedin()) { ?>
                    <a class="font-medium text-[#880101] underline decoration-[#EBA668] underline-offset-2" href="key">Get an API key (or view stats of existing keys)</a>.
                <?php } else {
                    ?>
                    <a class="font-medium text-[#880101] underline decoration-[#EBA668] underline-offset-2" href="key">Get an API key</a>.
                <?php } ?>
            </li>
            <li>All requests are made by GETting a particular URL with a number of parameters. <em>key</em> is required; <em>output</em> is optional, and defaults to <kbd class="rounded bg-oa-panel px-1.5 py-0.5 text-oa-brand">js</kbd>.</li>
        </ol>

        <p class="mt-5 rounded-lg bg-oa-brand px-4 py-3 text-center text-sm text-white">
            <strong class="break-all">http://www.openaustralia.org/api/<em>function</em>?key=<em>key</em>&amp;output=<em>output</em>&amp;<em>other_variables</em></strong>
        </p>

        <?php api_key_current_message(); ?>

        <p class="mt-4 text-sm text-oa-subheading">The current version of the API is <em>1.0.0</em>. If we make changes to the API functions, we'll increase the version number and make it an argument so you can still use the old version.</p>
    </div>

    <div class="mt-6 grid gap-4 md:grid-cols-2">
        <section class="rounded-xl border border-oa-border bg-white p-5 shadow-sm">
            <h3 class="text-lg font-semibold text-oa-heading">Outputs</h3>
            <p class="mt-2 text-sm text-oa-subheading">The <em>output</em> argument can take any of the following values:</p>
            <ul class="mt-3 list-disc space-y-2 pl-5 text-sm text-oa-brand">
                <li><strong>xml</strong>. XML. The root element is result.</li>
                <li><strong>php</strong>. Serialized PHP, that can be turned back into useful information with the unserialize() command. Quite useful in Python as well, using <a class="text-[#880101] underline decoration-[#EBA668] underline-offset-2" href="http://hurring.com/code/python/serialize/">PHPUnserialize</a>.</li>
                <li><strong>js</strong>. A JavaScript object. You can provide a callback function with the <em>callback</em> variable, and then that function will be called with the data as its argument.</li>
                <li><strong>rabx</strong>. "RPC over Anything But XML".</li>
            </ul>
        </section>
        <section class="rounded-xl border border-oa-border bg-white p-5 shadow-sm">
            <h3 class="text-lg font-semibold text-oa-heading">Errors</h3>
            <p class="mt-2 text-sm text-oa-brand">If there's an error, either in the arguments provided or in trying to perform the request, this is returned as a top-level error string, i.e. in XML it returns <code class="rounded bg-oa-panel px-1 py-0.5 text-oa-brand">&lt;result&gt;&lt;error&gt;ERROR&lt;/error&gt;&lt;/result&gt;</code>; in JS <code class="rounded bg-oa-panel px-1 py-0.5 text-oa-brand">{"error":"ERROR"}</code>; and in PHP and RABX a serialised array containing one entry with key <code class="rounded bg-oa-panel px-1 py-0.5 text-oa-brand">error</code>.</p>
        </section>
    </div>

        <section class="mt-6 rounded-xl border border-oa-border bg-white p-5 shadow-sm">
        <h3 class="text-lg font-semibold text-oa-heading">Sample Data Preview</h3>
        <p class="mt-2 text-sm text-oa-subheading">Example response data so you can quickly see API output shape and styling.</p>
        <pre class="mt-3 overflow-auto rounded-lg border border-oa-border bg-oa-paper p-4 text-xs text-oa-brand">{
    "person": {
        "id": "10567",
        "name": "Alex Example",
        "house": "representatives",
        "constituency": "Melbourne",
        "party": "Independent"
    },
    "recent_speeches": [
        {
            "date": "2026-05-28",
            "title": "Cost of living pressures",
            "words": 1243
        },
        {
            "date": "2026-05-21",
            "title": "Climate adaptation funding",
            "words": 892
        }
    ],
    "meta": {
        "output": "js",
        "version": "1.0.0"
    }
}</pre>
        </section>

    <section class="mt-6 rounded-xl border border-oa-border bg-white p-5 shadow-sm">
    <h3 class="text-lg font-semibold text-oa-heading">Licensing</h3>

    <p class="mt-3 text-sm text-oa-brand">
        Parliamentary material (that's data returned from getDebates and
        getHansard), is Copyright Commonwealth of Australia and is
        <a class="text-[#880101] underline decoration-[#EBA668] underline-offset-2" href="https://www.aph.gov.au/Help/Disclaimer_Privacy_Copyright#c">provided by them</a>
        under a <a href="http://creativecommons.org/licenses/by-nc-nd/3.0/au/">
            Creative Commons 3.0 Attribution-NonCommercial-NoDerivs</a> licence.
    </p>

    <p class="mt-3 text-sm text-oa-brand">Our own data - lists of members of the House of Representatives, Senators, electoral divisions and so on - is
        available
        under the <a class="text-[#880101] underline decoration-[#EBA668] underline-offset-2" href="http://creativecommons.org/licenses/by-sa/2.5/">Creative
            Commons Attribution-ShareAlike license version 2.5</a>.</p>

    <p class="mt-3 text-sm text-oa-brand">Low volume, non-commercial use of the API service itself is free. Please
        <a class="text-[#880101] underline decoration-[#EBA668] underline-offset-2" href="/contact">contact us</a> for commercial use, or if you are about
        to use the service on a large scale.
    </p>
    </section>

    <section class="mt-6 rounded-xl border border-oa-border bg-white p-5 shadow-sm">
    <h3 class="text-lg font-semibold text-oa-heading">Bindings</h3>

    <ul class="mt-3 list-disc space-y-2 pl-5 text-sm text-oa-brand">
        <li><a class="text-[#880101] underline decoration-[#EBA668] underline-offset-2" href="source/oaapi.phps">PHP source</a> and <a class="text-[#880101] underline decoration-[#EBA668] underline-offset-2" href="source/test.phps">example</a> (thanks to Mark Kinkade
            for adapting <a class="text-[#880101] underline decoration-[#EBA668] underline-offset-2" href="https://github.com/rubenarakelyan/twfyapi/">Ruben Arakelyan's TWFY bindings</a>)</li>
        <li><a class="text-[#880101] underline decoration-[#EBA668] underline-offset-2" href="https://github.com/henare/openaustralia-api/">Ruby</a>, by <a class="text-[#880101] underline decoration-[#EBA668] underline-offset-2" href="http://www.acooper.org/">Alex
                Cooper</a>, updated by <a href="https://www.henaredegan.com/">Henare Degan</a></li>
        <li><a class="text-[#880101] underline decoration-[#EBA668] underline-offset-2" href="https://github.com/rubenarakelyan/twfyapi/">PHP &amp; ASP.NET</a>, by <a class="text-[#880101] underline decoration-[#EBA668] underline-offset-2" href="http://ra.me.uk/">Ruben
                Arakelyan</a></li>
        <li><a class="text-[#880101] underline decoration-[#EBA668] underline-offset-2" href="https://pypi.python.org/pypi/openaustralia">Python</a>, by Chris Nilsson</li>
    </ul>

    <p class="mt-3 text-sm text-oa-brand">In adapting the API of TheyWorkForYou to OpenAustralia we've had to make a number of modifications which means that
        the language bindings developed for the <a class="text-[#880101] underline decoration-[#EBA668] underline-offset-2" href="http://theyworkforyou.com/api">TheyWorkForYou API</a> won't
        directly work with the OpenAustralia API. If anyone wishes to adapt them or write new bindings, please
        do so, let us know and we'll link to it here. You might want to <a class="text-[#880101] underline decoration-[#EBA668] underline-offset-2"
            href="https://groups.google.com/group/openaustralia-dev">join the OpenAustralia community mailing list</a>
        to discuss things.</p>
    </section>

    <section class="mt-6 rounded-xl border border-oa-border bg-white p-5 shadow-sm">
    <h3 class="text-lg font-semibold text-oa-heading">Examples</h3>

    <ul class="mt-3 list-disc space-y-2 pl-5 text-sm text-oa-brand">
        <li><a class="text-[#880101] underline decoration-[#EBA668] underline-offset-2" href="http://code.google.com/p/poli-press/">PoliPress</a> is a <a class="text-[#880101] underline decoration-[#EBA668] underline-offset-2"
                href="https://www.wordpress.org/">WordPress</a> plugin that lets you quote members of parliament and search
            the Australian Hansard from within your WordPress blog.</li>
        <li><a class="text-[#880101] underline decoration-[#EBA668] underline-offset-2" href="https://github.com/henare/oa4wp/">OpenAustralia for WordPress</a> is another WordPress plugin. It
            displays your MP's most recent speeches on your blog.</li>
    </ul>
    </section>

    <?php
    $sidebar = api_sidebar();
    $PAGE->stripe_end([$sidebar]);
    $PAGE->page_end();
}
