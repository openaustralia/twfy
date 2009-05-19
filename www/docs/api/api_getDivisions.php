<?

include_once 'api_getConstituencies.php';

function api_getDivisions_front() {
	api_getConstituencies_front();
}

function api_getDivisions_search($s) {
	api_getConstituencies_search($s);
}

function api_getDivisions_date($date) {
	api_getConstituencies_date($date);
}

function api_getDivisions($date = 'now()') {
	api_getConstituencies($date);
}

?>
