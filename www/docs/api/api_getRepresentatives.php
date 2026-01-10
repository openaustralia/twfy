<?php

include_once 'api_getMP.php';
include_once 'api_getMPs.php';

function api_getRepresentatives_front() {
	api_getMPs_front();
}

function api_getDivisions_postcode($pc) {
	$pc = preg_replace('#[^0-9]#i', '', $pc);
	$output = array();
	if (is_postcode($pc)) {
		$constituency = postcode_to_constituency($pc);
		if ($constituency == 'CONNECTION_TIMED_OUT') {
			api_error('Connection timed out');
		} elseif ($constituency) {
			if (is_array($constituency)) {
				$constituencies = $constituency;
			}
			else {
				$constituencies = array($constituency);
			}
			foreach ($constituencies as $c) {
				$output[] = array('name' => html_entity_decode($c));
			}
		} else {
			api_error('Unknown postcode');
		}
	} else {
		api_error('Invalid postcode');
	}
	api_output($output);
}


function api_getRepresentatives_postcode($pc) {
	$pc = preg_replace('#[^0-9]#i', '', $pc);
	if (is_postcode($pc)) {
		$constituency = postcode_to_constituency($pc);
		if ($constituency == 'CONNECTION_TIMED_OUT') {
			api_error('Connection timed out');
		} elseif ($constituency) {
			if (is_array($constituency))
				$constituencies = $constituency;
			else
				$constituencies = array($constituency);
			$output = array();
			foreach ($constituencies as $c) {
				$output[] = _api_getMP_constituency($c);
			}
			api_output($output);
		} else {
			api_error('Unknown postcode');
		}
	} else {
		api_error('Invalid postcode');
	}
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
