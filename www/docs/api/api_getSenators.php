<?

include_once 'api_getLords.php';

function api_getSenators_front() {
	api_getLords_front();
}

function api_getSenators_party($s) {
	api_getLords_party($s);
}
function api_getSenators_state($s) {
        api_getLords_state($s);
}
function api_getSenators_search($s) {
	api_getLords_search($s);
}
function api_getSenators_date($date) {
	api_getLords_date($date);
}
function api_getSenators($date = 'now()') {
	api_getLords($date);
}

?>
