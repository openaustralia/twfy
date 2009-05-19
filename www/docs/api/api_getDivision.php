<?

include_once 'api_getConstituency.php';

function api_getDivision_front() {
	api_getConstituency_front();
}

function api_getdivision_postcode($pc) {
	api_getconstituency_postcode($pc);
}

?>
