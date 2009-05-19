<?

include_once 'api_getLord.php';

function api_getSenator_front() {
	api_getLord_front();
}

function api_getSenator_id($id) {
	api_getLord_id($id);
}

?>
