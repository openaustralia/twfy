<?php

include_once 'api_getMP.php';

function api_getRepresentative_front() {
	api_getMP_front();
}

function api_getRepresentative_id($id) {
	api_getMP_id($id);
}

function api_getRepresentative_division($constituency) {
	api_getMP_constituency($constituency);
}

?>
