<?php
//
// Include the API binding
require_once 'oaapi.php';

// Set up a new instance of the API binding
$oaapi = new OAAPI('YOUR_API_KEY_HERE');

// Get a list of Labour MPs in XML format
$mps = $oaapi->query('getRepresentatives', array('output' => 'xml', 'postcode' => '2000'));

// Print out the list
echo $mps;

?>
