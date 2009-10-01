<?

include_once '../../includes/easyparliament/init.php';
include_once '../../includes/postcode.inc';

include_once 'api_functions.php';

# XXX: Need to override error handling! XXX

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
	foreach ($methods as $method => $data) {
		if (strtolower($q_method) == strtolower($method)) {
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
					api_call_user_func_or_error('api_' . $method . '_' . $parameter, array($q_param), 'API call not yet functional', 'api');
					break;
				}
			}
			if ($match == 1 && (get_http_var('output') || !get_http_var('docs'))) {
				if ($data['required']) {
					api_error('No parameter provided to function "' .
					htmlspecialchars($q_method) .
						'". Possible choices are: ' .
						join(', ', $data['parameters']) );
				} else {
					include_once 'api_' . $method . '.php';
					api_call_user_func_or_error('api_' . $method, null, 'API call not yet functional', 'api');
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
			join(', ', array_keys($methods)) );
	} else {
		if (get_http_var('docs')) {
			$explorer = ob_get_clean();
			api_documentation_front($method, $explorer);
		}
	}
} else {
	api_front_page();
}

function api_documentation_front($method, $explorer) {
	global $PAGE, $this_page, $DATA, $methods;
	$this_page = 'api_doc_front';
	$DATA->set_page_metadata($this_page, 'title', "$method function");
	$PAGE->page_start();
	$PAGE->stripe_start();
	include_once 'api_' . $method . '.php';
	print '<p align="center"><strong>http://www.openaustralia.org/api/' . $method . '</strong></p>';
	api_call_user_func_or_error('api_' . $method . '_front', null, 'No documentation yet', 'html');
?>
<h4>Explorer</h4>
<p>Try out this function without writing any code!</p>
<form method="get" action="?#output">
<p>
<? foreach ($methods[$method]['parameters'] as $parameter) {
	print $parameter . ': <input type="text" name="'.$parameter.'" value="';
	if ($val = get_http_var($parameter))
		print htmlspecialchars($val);
	print '" size="30"><br>';
}
?>
Output:
<input id="output_js" type="radio" name="output" value="js"<? if (get_http_var('output')=='js' || !get_http_var('output')) print ' checked'?>>
<label for="output_js">JS</label>
<input id="output_xml" type="radio" name="output" value="xml"<? if (get_http_var('output')=='xml') print ' checked'?>>
<label for="output_xml">XML</label>
<input id="output_php" type="radio" name="output" value="php"<? if (get_http_var('output')=='php') print ' checked'?>>
<label for="output_php">Serialised PHP</label>
<input id="output_rabx" type="radio" name="output" value="rabx"<? if (get_http_var('output')=='rabx') print ' checked'?>>
<label for="output_rabx">RABX</label>

<input type="submit" value="Go">
</p>
</form>
<?
	if ($explorer) {
		$qs = array();
		foreach ($methods[$method]['parameters'] as $parameter) {
			if (get_http_var($parameter))
				$qs[] = htmlspecialchars(rawurlencode($parameter) . '=' . urlencode(get_http_var($parameter)));
		}
		print '<h4><a name="output"></a>Output</h4>';
		print '<p>URL for this: <strong>http://www.openaustralia.org/api/';
		print $method . '?' . join('&amp;', $qs) . '&amp;output='.get_http_var('output').'</strong></p>';
		print '<pre>' . htmlspecialchars($explorer) . '</pre>';
	}
	$sidebar = api_sidebar();
	$PAGE->stripe_end(array($sidebar));
	$PAGE->page_end();
}

function api_front_page($error = '') {
	global $PAGE, $methods, $this_page, $THEUSER;
	$this_page = 'api_front';
	$PAGE->page_start();
	$PAGE->stripe_start();
	if ($error) {
		print "<p style='color: #cc0000'>$error</p>";
	}
?>
<p>Welcome to OpenAustralia's API section, where you can learn how to query our database for information.</p>

<h3>Overview</h3>

<ol style="font-size:130%">
<li>
<? if ($THEUSER->loggedin()) { ?>
<a href="key">Get an API key (or view stats of existing keys)</a>.
<? } else { ?>
<a href="key">Get an API key</a>.
<? } ?>
<li>All requests are made by GETting a particular URL with a number of parameters. <em>key</em> is required;
<em>output</em> is optional, and defaults to <kbd>js</kbd>.
</ol>

<p align="center"><strong>http://www.openaustralia.org/api/<em>function</em>?key=<em>key</em>&amp;output=<em>output</em>&amp;<em>other_variables</em></strong></p>

<? api_key_current_message(); ?>

<p>The current version of the API is <em>1.0.0</em>. If we make changes to the
API functions, we'll increase the version number and make it an argument so you
can still use the old version.</p>

<table>
<tr valign="top">
<td width="60%">

<h3>Outputs</h3>
<p>The <em>output</em> argument can take any of the following values:
<ul>
<li><strong>xml</strong>. XML. The root element is result.</li>
<li><strong>php</strong>. Serialized PHP, that can be turned back into useful information with the unserialize() command. Quite useful in Python as well, using <a href="http://hurring.com/code/python/serialize/">PHPUnserialize</a>.</li>
<li><strong>js</strong>. A JavaScript object. You can provide a callback
function with the <em>callback</em> variable, and then that function will be
called with the data as its argument.</li>
<li><strong>rabx</strong>. "RPC over Anything But XML".</li>
</ul>

</td><td>

<h3>Errors</h3>

<p>If there's an error, either in the arguments provided or in trying to perform the request,
this is returned as a top-level error string, ie. in XML it returns
<code>&lt;result&gt;&lt;error&gt;ERROR&lt;/error&gt;&lt;/result&gt;</code>;
in JS <code>{"error":"ERROR"}</code>;
and in PHP and RABX a serialised array containing one entry with key <code>error</code>.

</td></tr></table>

<h3>Licensing</h3>

<p>To use parliamentary material yourself (that's data returned from
getDebates and getHansard), which is Copyright Commonwealth of Australia, you will need to obtain permission to republish it. When we sought permission, we were told that the material is "considered in the public domain". We're not quite sure what that means so you best check for yourself.</p>

<p>Our own data - lists of members of the House of Representatives, Senators, electoral divisions and so on - is available
under the <a href="http://creativecommons.org/licenses/by-sa/2.5/">Creative
Commons Attribution-ShareAlike license version 2.5</a>.</p>

<p>Low volume, non-commercial use of the API service itself is free. Please
<a href="/contact">contact us</a> for commercial use, or if you are about
to use the service on a large scale.</p>

<h3>Bindings</h3>

<ul>
    <li><a href="source/oaapi.phps">PHP source</a> and <a href="source/test.phps">example</a> (thanks to Mark Kinkade for adapting <a href="http://tools.wackomenace.co.uk/twfyapi/">Ruben Arakelyan's TWFY bindings</a>)</li>
</ul>

<p>In adapting the API of TheyWorkForYou to OpenAustralia we've had to make a number of modifications which means that the language bindings developed for the <a href="http://theyworkforyou.com/api">TheyWorkForYou API</a> won't directly work with the OpenAustralia API. If anyone wishes to adapt them or write new bindings, please
do so, let us know and we'll link to it here. You might want to <a href="http://groups.google.com/group/openaustralia-dev">join the OpenAustralia community mailing list</a>
to discuss things.</p>

<h3>Examples</h3>

<ul>
<li><a href="http://code.google.com/p/poli-press/">PoliPress</a> is a <a href="http://www.wordpress.org/">WordPress</a> plugin that lets you quote members of parliament and search the Australian Hansard from within your WordPress blog.</li>
</ul>

<?
	$sidebar = api_sidebar();
	$PAGE->stripe_end(array($sidebar));
	$PAGE->page_end();
}
