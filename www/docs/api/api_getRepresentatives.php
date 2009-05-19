<?

include_once 'api_getMPs.php';

function api_getRepresentatives_front() {
	api_getMPs_front();
}

function api_getRepresentatives_party($s) {
	api_getMPs_party($s);
}

function api_getRepresentatives_search($s) {
	api_getMPs_search($s);
}

function api_getRepresentatives_date($date) {
	api_getMPs_date($date);
}

function api_getRepresentatives($date = 'now()') {
	api_getMPs($date);
}

?>
