<?

include_once 'api_getMembers.php';

function api_getLords_front() {
?>
<p><big>Fetch a list of Senators.</big></p>

<h4>Arguments</h4>
<dl>
<dt>date (optional)</dt>
<dd>Fetch the list of Senators as it was on this date.</dd>
<dt>party (optional)</dt>
<dd>Fetch the list of Senators from the given party.</dd>
<dt>state (optional)</dt>
<dd>Fetch the list of Senators from the given state.</dd>
<dt>search (optional)</dt>
<dd>Fetch the list of Senators that match this search string in their name.</dd>
</dl>

<h4>Example Response</h4>
<pre>
&lt;result&gt;
	&lt;match&gt;
		&lt;member_id&gt;100077&lt;/member_id&gt;
		&lt;person_id&gt;10214&lt;/person_id&gt;
		&lt;name&gt;John Faulkner&lt;/name&gt;
		&lt;party&gt;Australian Labor Party&lt;/party&gt;
	&lt;/match&gt;
	&lt;match&gt;
		&lt;member_id&gt;100261&lt;/member_id&gt;
		&lt;person_id&gt;10716&lt;/person_id&gt;
		&lt;name&gt;John Williams&lt;/name&gt;
		&lt;party&gt;National Party&lt;/party&gt;
	&lt;/match&gt;	
	...
&lt;/result&gt;
</pre>
<?	
}

/* See api_getMembers.php for these shared functions */
function api_getLords_party($s) {
	api_getMembers_party(2, $s);
}
function api_getLords_state($s) {
        api_getMembers_state(2, $s);
}
function api_getLords_search($s) {
	api_getMembers_search(2, $s);
}
function api_getLords_date($date) {
	api_getMembers_date(2, $date);
}
function api_getLords($date = 'now()') {
	api_getMembers(2, $date);
}

?>
