<?php

/**
 * @file
 * MYSQL class .
 *
 * Depends on having the debug() and getmicrotime() functions available elsewhere to output debugging info.
 *
 *
 * Somewhere (probably in includes/easyparliament/init.php) there should be something like:
 *
 * Class ParlDB extends MySQL {
 * function ParlDB () {
 * $this->init (DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
 * }
 * }
 *
 * Then, when you need to do queries, you do:
 *
 * $db = new ParlDB;
 * $q = $db->query("SELECT haddock FROM fish");
 *
 * $q is then a MySQLQuery object.
 *
 * If other databases are needed, we just need to create a class for each, each one
 * extending MySQL.
 *
 *
 * Call $db->display_total_duration() at the end of a page to send total query time to debug().
 *
 *
 * (n is 0-based below...)
 *
 * After a SELECT
 * ==============
 * If successful:
 * $q->success() returns true.
 * $q->rows() returns the number of rows selected
 * $q->row(n) returns an array of the nth row, with the keys being column names.
 * $q->field(n,col) returns the contents of the "col" column in the nth row.
 * $q->insert_id() returns NULL.
 * $q->affected_rows() returns NULL.
 * If 0 rows selected:
 * $q->success() returns true.
 * $q->rows() returns 0.
 * $q->row(n) returns an empty array.
 * $q->field(n,col) returns "".
 * $q->insert_id() returns NULL.
 * $q->affected_rows() returns NULL.
 *
 * After an INSERT
 * ===============
 * If successful:
 * $q->success() returns true.
 * $q->rows() returns NULL.
 * $q->row(n) returns an empty array.
 * $q->field(n,col) returns "".
 * $q->insert_id() returns the last_insert_id (if there's AUTO_INCREMENT on a column).
 * $q->affected_rows() returns 1.
 *
 * After an UPDATE
 * ===============
 * If rows have been changed:
 * $q->success() returns true.
 * $q->rows() returns NULL.
 * $q->row(n) returns an empty array.
 * $q->field(n,col) returns "".
 * $q->insert_id() returns 0.
 * $q->affected_rows() returns the number of rows changed.
 *
 * After a DELETE
 * ==============
 * If rows have been deleted:
 * $q->success() returns true.
 * $q->rows() returns NULL.
 * $q->row(n) returns an empty array.
 * $q->field(n,col) returns "".
 * $q->insert_id() returns 0.
 * $q->affected_rows() returns the number of rows changed.
 * If no rows are deleted:
 * $q->success() returns true.
 * $q->rows() returns NULL.
 * $q->row(n) returns an empty array.
 * $q->field(n,col) returns "".
 * $q->insert_id() returns 0.
 * $q->affected_rows() returns 0.
 *
 *
 * If there's an error for any of the above actions:
 * $q->success() returns false.
 * $q->rows() returns NULL.
 * $q->row(n) returns an empty array.
 * $q->field(n,col) returns "".
 * $q->insert_id() returns NULL.
 * $q->affected_rows() returns NULL.
 *
 *
 * Versions
 * ========
 * v1.2    2003-11-25
 * Changed to using named constants, rather than global variables.
 */

// We'll add up the times of each query so we can output the page total at the end.
global $mysqltotalduration;
$mysqltotalduration = 0.0;

/**
 *
 */
class MySQLQuery {

    public $fieldnames_byid = [];
    public $fieldnames_byname = [];
    public $success = TRUE;
    public $rows = NULL;
    public $fields = 0;
    public $data = [];
    public $insert_id = NULL;
    public $affected_rows = NULL;

    /**
     *
     */
    public function MySQLQuery($conn) {
        $this->conn = $conn;
    }

    /**
     *
     */
    public function query($sql = "") {

        if (empty($sql)) {
            $this->success = FALSE;
            return;
        }

        if (empty($this->conn)) {
            $this->success = FALSE;
            return;
        }

        twfy_debug("SQL", $sql);

        $q = mysqli_query($this->conn, $sql);
        if (!$q) {
            $this->error(mysqli_errno($this->conn) . ": " . mysqli_error($this->conn));
        }

        if ($this->success) {
            if ((!$q) or (empty($q))) {
                // A failed query.

                $this->success = FALSE;

                return;

            }
            elseif (is_bool($q)) {
                // A successful query of a type *other* than
                // SELECT, SHOW, EXPLAIN or DESCRIBE.

                // For INSERTs that have generated an id from an AUTO_INCREMENT column.
                $this->insert_id = mysqli_insert_id($this->conn);

                $this->affected_rows = mysqli_affected_rows($this->conn);

                $this->success = TRUE;

                return;

            }
            else {

                // A successful SELECT, SHOW, EXPLAIN or DESCRIBE query.
                $this->success = TRUE;

                $result = [];
                for ($i = 0; $i < mysqli_num_fields($q); $i++) {
                    $field_info = mysqli_fetch_field_direct($q, $i);
                    $fieldnames_byid[$i] = $field_info->name;
                    $fieldnames_byname[$field_info->name] = $i;
                }

                while ($row = mysqli_fetch_row($q)) {
                    $result[] = $row;
                }

                if (sizeof($result) > 0) {
                    $this->rows = sizeof($result);
                }
                else {
                    $this->rows = 0;
                }

                $this->fieldnames_byid = $fieldnames_byid;
                $this->fieldnames_byname = $fieldnames_byname;
                $this->fields = sizeof($fieldnames_byid);
                $this->data = $result;

                twfy_debug("SQLRESULT", $this->_display_result());

                mysqli_free_result($q);

                return;
            }
        }
        else {
            // There was an SQL error.
            return;
        }

    }

    /**
     *
     */
    public function success() {
        return $this->success;
    }

    /**
     * After INSERTS.
     */
    public function insert_id() {
        return $this->insert_id;
    }

    /**
     * After INSERT, UPDATE, DELETE.
     */
    public function affected_rows() {
        return $this->affected_rows;
    }

    /**
     * After SELECT.
     */
    public function field($row_index, $column_name) {
        if ($this->rows > 0) {
            // Old slow version
            // $result = $this->_row_array($row_index);
            // return $result[$column_name];.

            // New faster version.
            $result = $this->data[$row_index][$this->fieldnames_byname[$column_name]];
            return $result;
        }
        else {
            return "";
        }
    }

    /**
     * After SELECT.
     */
    public function rows() {
        return $this->rows;
    }

    /**
     * After SELECT.
     */
    public function row($row_index) {
        if ($this->success) {
            $result = $this->_row_array($row_index);
            return $result;
        }
        else {
            return [];
        }
    }

    /**
     *
     */
    public function _row_array($row_index) {
        $result = [];
        if ($this->rows > 0) {
            $fields = $this->data[$row_index];

            foreach ($fields as $index => $data) {
                $fieldname = $this->fieldnames_byid[$index];
                $result[$fieldname] = $data;
            }
        }

        return $result;
    }

    /**
     *
     */
    public function _display_result() {

        $html = "";

        if (count($this->fieldnames_byid) > 0) {

            $html .= "<table border=\"1\">\n<tr>\n";

            foreach ($this->fieldnames_byid as $index => $fieldname) {
                $html .= "<th>" . htmlentities($fieldname) . "</th>";
            }
            $html .= "</tr>\n";

            foreach ($this->data as $index => $row) {
                $html .= "<tr>";
                foreach ($row as $n => $field) {
                    if ($this->fieldnames_byid[$n] == "email" || $this->fieldnames_byid[$n] == "password" || $this->fieldnames_byid[$n] == "postcode") {
                        // Don't want to risk this data being displayed on any page.
                        $html .= "<td>**MASKED**</td>";
                    }
                    else {
                        $html .= "<td>" . htmlentities($field) . "</td>";
                    }
                }
                $html .= "</tr>\n";
            }
            $html .= "</table>\n";

        }

        return $html;
    }

    /**
     *
     */
    public function error($errormsg) {
        // When a query goes wrong...
        $this->success = FALSE;

        trigger_error($errormsg, E_USER_ERROR);

        return;
    }

    // End MySQLQuery class.
}

$global_connection = NULL;

/**
 *
 */
class MySQL {

    /**
     *
     */
    public function init($db_host, $db_user, $db_pass, $db_name) {
        global $global_connection;
        // These vars come from config.php.

        if (!$global_connection) {
            $conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name);
            if (!$conn) {
                print ("<p>DB connection attempt failed: " . mysqli_connect_error() . "</p>");
                exit;
            }
            if (!mysqli_select_db($conn, $db_name)) {
                print ("<p>DB select failed</p>");
                exit;
            }
            $global_connection = $conn;
        }
        $this->conn = $global_connection;

        // Select default character set.
        $q = new MySQLQuery($this->conn);

        return TRUE;
    }

    /**
     *
     */
    public function query($sql) {
        // Pass it an SQL query and if the query was successful
        // it returns a MySQLQuery object which you can get results from.

        $start = getmicrotime();
        $q = new MySQLQuery($this->conn);
        $q->query($sql);

        $duration = getmicrotime() - $start;
        global $mysqltotalduration;
        $mysqltotalduration += $duration;
        twfy_debug("SQL", "Complete after $duration seconds.");
        // We could also output $q->mysql_info() here, but that's for
        // PHP >= 4.3.0.

        return $q;

    }

    /**
     *
     */
    public function escape($str) {
        return mysqli_real_escape_string($this->conn, $str);
    }

    /**
     * Call at the end of a page.
     */
    public function display_total_duration() {
        global $mysqltotalduration;
        twfy_debug("TIME", "Total time for MySQL queries on this page: " . $mysqltotalduration . " seconds.");
    }

    // End MySQL class.
}
