<?php

error_reporting(E_ALL);
ini_set('memory_limit', 16 * 1024 * 1024);

include_once "../../../conf/general";
include_once __DIR__ . "/../../includes/utility.php";
include_once __DIR__ . "/../../includes/mysql.php";

/**
 *
 */
class ParlDB extends MySQL {

    /**
     *
     */
    public function __construct() {
        $this->init(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    }

}
