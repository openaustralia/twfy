<?php

// **********************************************************************
// OA::API PHP API interface for OpenAustralia.org
// Version 1.0
// Author: Mark Kinkade <pagetribemail@gmail.com>
//
// This work has been adapted/extended from the great work done by:
// Ruben Arakelyan <ruben@wackomenace.co.uk>
// Copyright (C) 2008 Ruben Arakelyan. Some rights reserved.
//
// This file is licensed under the
// Creative Commons Attribution-ShareAlike license version 2.5
// available at http://creativecommons.org/licenses/by-sa/2.5/
//
// For more information, see http://tools.wackomenace.co.uk/twfyapi/
//
// Inspiration: WebService::TWFY::API by Spiros Denaxas
// Available at: http://search.cpan.org/~sden/WebService-TWFY-API-0.01/
// **********************************************************************

class OAAPI {

    // API key
    private $key;

    // cURL handle
    private $ch;

    // Default constructor
    public function __construct($key) {
        // Check and set API key
        if (!$key) {
            die('ERROR: No API key provided.');
        }
        if (!preg_match('/^[A-Za-z0-9]+$/', $key)) {
            die('ERROR: Invalid API key provided.');
        }
        $this->key = $key;

        // Create a new instance of cURL
        $this->ch = curl_init();

        // Set the user agent
        // It does not provide TheyWorkForYou.com with any personal information
        // but helps them track usage of this PHP class.
        curl_setopt($this->ch, CURLOPT_USERAGENT, 'OA::API PHP class');

        // Return the result
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
    }

    // Default destructor
    public function __destruct() {
        // Delete the instance of cURL
        curl_close($this->ch);
    }

    // Send an API query
    public function query($func, $args = array()) {
        // Exit if any arguments are not defined
        if (!isset($func) || $func == '' || !isset($args) || $args == '' || !is_array($args)) {
            die('ERROR: Function name or arguments not provided.');
        }

        // Construct the query
        $query = new OAAPI_Request($func, $args, $this->key);

        // Execute the query
        if (is_object($query)) {
            return $this->_execute_query($query);
        }
        else {
            die('ERROR: Could not assemble request using OAAPI_Request.');
        }
    }

    // Execute an API query
    private function _execute_query($query) {
        // Make the final URL
        $URL = $query->encode_arguments();

        // Set the URL
        curl_setopt($this->ch, CURLOPT_URL, $URL);

        // Get the result
        $result = curl_exec($this->ch);

        // Find out if all is OK
        if (!$result) {
            die('ERROR: cURL error occurred: ' . curl_error($this->ch));
        }
        else {
            return $result;
        }
    }

}


class OAAPI_Request {

    // API URL
    private $URL = 'http://www.openaustralia.org/api/';

    // Chosen function, arguments and API key
    private $func;
    private $args;

    // Default constructor
    public function __construct($func, $args, $key) {
        // Set function, arguments and API key
        $this->func = $func;
        $this->args = $args;
        $this->key = $key;

        // Get and set the URL
        $this->URL = $this->_get_uri_for_function($this->func);

        // Check to see if valid URL has been set
        if (!isset($this->URL) || $this->URL == '') {
            die('ERROR: Invalid function: ' . $this->func . '. Please look at the documentation for supported functions.');
        }
    }

    // Encode function arguments into a URL query string
    public function encode_arguments() {
        // Validate the output argument if it exists
        if (array_key_exists('output', $this->args)) {
            if (!$this->_validate_output_argument($this->args['output'])) {
                return '';
            }
        }

        // Make sure all mandatory arguments for a particular function are present
        if (!$this->_validate_arguments($this->func, $this->args)) {
            return '';
        }

        // Assemble the URL
        $full_url = $this->URL . '?key=' . $this->key . '&';
        foreach ($this->args as $name => $value) {
            $full_url .= $name . '=' . $value . '&';
        }
        $full_url = substr($full_url, 0, -1);

        return $full_url;
    }

    // Get the URL for a particular function
    private function _get_uri_for_function($func) {
        // Exit if any arguments are not defined
        if (!isset($func) || $func == '') {
            return '';
        }

        // Define valid functions
        $valid_functions = array(
            'convertURL'        => 'Converts a parliament.uk URL into a TheyWorkForYou one, if possible',
            'getDivisions'    => 'Returns list of electoral divisions',
            'getRepresentative'  => 'Returns main details for a member of the House of Representatives',
            'getRepresentatives' => 'Returns main details for a member of the House of Representatives',
            'getSenator' => 'Returns details for a Senator',
            'getSenators' => 'Returns list of Senators',
            'getDebates' => 'Returns Debates (either House of Representatives or Senate)',
            'getHansard' => 'Returns any of the above',
            'getComments' => 'Returns comments',
        );

        // If the function exists, return its URL
        if (array_key_exists($func, $valid_functions)) {
            return $this->URL . $func;
        }
        else {
            return '';
        }
    }

    // Validate the "output" argument
    private function _validate_output_argument($output) {
        // Exit if any arguments are not defined
        if (!isset($output) || $output == '') {
            return false;
        }

        // Define valid output types
        $valid_params = array(
          'xml'  => 'XML output',
          'php'  => 'Serialized PHP',
          'js'   => 'a JavaScript object',
          'rabx' => 'RPC over Anything But XML',
        );

        // Check to see if the output type provided is valid
        if (array_key_exists($output, $valid_params)) {
            return true;
        }
        else {
            die('ERROR: Invalid output type: ' . $output . '. Please look at the documentation for supported output types.');
        }
    }

    // Validate arguments
    private function _validate_arguments($func, $args) {
        // Define manadatory arguments. Value in array represents mandatory variables
        $functions_params = array(
            'convertURL' => array( 'url' ),
            'getDivisions' => array(),
            'getRepresentative'  => array(),
            'getRepresentatives' => array(),
            'getSenator' => array(),
            'getSenators' => array(),
            'getDebates' => array('type'),
            'getHansard' => array(),
            'getComments' => array(),
        );

        // Check to see if all mandatory arguments are present
        $required_params = $functions_params[$func];
        foreach ($required_params as $param) {
            if (!isset($args[$param])) {
                die('ERROR: All manadatory arguments for ' . $func . ' not provided.');
            }
        }

        return true;
    }


}

?>
