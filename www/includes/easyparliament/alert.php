<?php

/**
 * @file
 * NO HTML IN THIS FILE!!
 *
 * // Name: alert.php
 * // Author:  Richard Allan richard@sheffieldhallam.org.uk
 * // Version: 0.5 beta
 * // Date: 6th Jan 2005
 * // Description:  This file contains ALERT class.
 *
 * The ALERT class allows us to fetch and alter data about any email alert.
 * Functions here:
 *
 * ALERT
 * fetch($confirmed, $deleted)        Fetch all alert data from DB.
 * listalerts()                    Lists all live alerts
 * add($details, $confirmation_email)    Add a new alert to the DB.
 * send_confirmation_email($details)    Done after add()ing the alert.
 * email_exists($email)            Checks if an alert exists with a certain email address.
 * confirm($token)                Confirm a new alert in the DB
 * delete($token)                Remove an existing alert from the DB
 * id_exists()                Checks if an alert_id is valid.
 *
 * Accessor functions for each object variable (eg, alert_id()  ).
 *
 * To create a new alert do:
 * $ALERT = new ALERT;
 * $ALERT->add();
 *
 * You can then access all the alert's variables with appropriately named functions, such as:
 * $ALERT->alert_id();
 * $ALERT->email();
 * etc.
 */

/**
 * CLASS:  ALERT.
 */
function suggest_alerts($email, $criteria, $maxresults) {
    $db = new ParlDB();
    // Speaker only.
    if (stripos($criteria, "speaker:") == 0) {
        // Find emails who follow this speaker
        // find the speakers followed by those email
        // return their most frequently followed speakers, not followed by the searcher.
        // Select and count criteria.
        $sql = "SELECT count(*) AS c, criteria FROM alerts ";
        // From emails which have the provided criteria/pid.
        $sql .= "WHERE email = any (SELECT email FROM alerts WHERE criteria like '%$criteria%') ";
        // Filter in simple speaker alerts 'speaker:nnnnn'.
        $sql .= "AND LENGTH(criteria)=13.AND LEFT(criteria,8)='speaker:' ";
        // Disregard any alert of this emailer (already following)
        $sql .= "AND NOT(criteria=ANY(SELECT criteria FROM alerts WHERE email='$email')) ";
        // $sql.="AND email like '%foo.test%' "; // filter in my test alerts  // REMOVE ME.
        // Most commo first.
        $sql .= "GROUP BY criteria ORDER BY c DESC";
        $q = $db->query($sql);
        $resultcount = $q->rows();
        // If something was returned.
        if ($resultcount > 0) {
            print "<p>You may also be interested in being alerted when these people speak too.</p>";
        } {
        if ($resultcount > $maxresults) {
            // Cap results.
            $resultcount = $maxresults;
        }

        // Iterate through results.
        for ($i = 0; $i < $resultcount; $i++) {
            // Ignore suggestion where only one other has an alert for.
            if ($q->field($i, 'c') > 1) {
                // Extract members PID.
                $pid = substr($q->field($i, 'criteria'), -5);
                $member = new MEMBER(['person_id' => $pid]);
                print '<p><a href="' . WEBPATH . 'alert/?r=1&only=1&amp;pid=' . $member->person_id() . '"><strong>Email me whenever ' . $member->full_name() . ' speaks</strong></a></p>';
            }
        }
        }
    }
}

/**
 *
 */
function alert_confirmation_advert($details) {
    if ($details['pid']) {
        $advert_shown = 'twfy-alert-word';
        ?>
        <p>Did you know that OpenAustralia can also email you when a certain word or phrases is mentioned in parliament? For
            example, it could mail you when your town is mentioned, or an issue you care about. Don't rely on the newspapers to
            keep you informed about your interests - find out what's happening straight from the horse's mouth.
            <a href="<?php echo WEBPATH ?>alert/"><strong>Sign up for an email alert</strong></a>
        </p>
    <?php }
    else {
        $advert_shown = 'twfy-alert-person';
        ?>
        <p>Did you know that OpenAustralia can also email you when a certain representative contributes in parliament? Don't
            rely on the newspapers to keep you informed about someone you're interested in - find out what's happening straight
            from the horse's mouth.
            <a href="<?php echo WEBPATH ?>alert/"><strong>Sign up for an email alert</strong></a>
        </p>
    <?php }
    return $advert_shown;
}

/**
 *
 */
function alert_details_to_criteria($details) {
    $criteria = [];
    if (isset($details['keyword']) && $details['keyword']) {
        $criteria[] = $details['keyword'];
    }
    if ($details['pid']) {
        $criteria[] = 'speaker:' . $details['pid'];
    }
    $criteria = join(' ', $criteria);
    return $criteria;
}

/**
 *
 */
class ALERT {

    public $alert_id = "";
    public $email = "";
    /**
     * Sets the terms that are used to prdduce the search results.
     */
    public $criteria = "";
    public $deleted = "";        /**
                                  * Flag set when user requests deletion of alert.
                                  */
    /**
     * Boolean - Has the user confirmed via email?
     */
    public $confirmed = "";

    /**
     *
     */
    public function ALERT() {
        $this->db = new ParlDB();
    }

    /**
     * FUNCTION: fetch.
     */
    public function fetch($confirmed, $deleted) {
        // Pass it an alert id and it will fetch data about alerts from the db
        // and put it all in the appropriate variables.
        // Normal usage is for $confirmed variable to be set to true
        // and $deleted variable to be set to false
        // so that only live confirmed alerts are chosen.

        // Look for this alert_id's details.
        $q = $this->db->query("SELECT alert_id,
						email,
						criteria,
						registrationtoken,
						deleted,
						confirmed
						FROM alerts
						WHERE confirmed =" . $confirmed .
          " AND deleted=" . $deleted .
          ' ORDER BY email');

        $data = [];

        for ($row = 0; $row < $q->rows(); $row++) {
            $contents = [
              'alert_id' => $q->field($row, 'alert_id'),
              'email' => $q->field($row, 'email'),
              'criteria' => $q->field($row, 'criteria'),
              'registrationtoken' => $q->field($row, 'registrationtoken'),
              'confirmed' => $q->field($row, 'confirmed'),
              'deleted' => $q->field($row, 'deleted')
          ];
            $data[] = $contents;
        }
        $info = "Alert";
        $data = ['info' => $info, 'data' => $data];

        return $data;
    }

    /**
     * FUNCTION: listalserts.
     */
    public function listalerts() {

        // Lists all live alerts.

        $tmpdata = [];
        $confirmed = '1';
        $deleted = '0';

        // Get all the data that's to be returned.
        $tmpdata = $this->fetch($confirmed, $deleted);
        // Foreach ($tmpdata as $n => $data)
        //                {
        //                    echo "Alert: " . $data['email'] . " and " . $data['criteria'];
        //                }.
    }

    /**
     * FUNCTION: add.
     */
    public function add($details, $confirmation_email = FALSE, $instantly_confirm = TRUE) {

        // Adds a new alert's info into the database.
        // Then calls another function to send them a confirmation email.
        // $details is an associative array of all the alert's details, of the form:
        // array (
        //        "email" => "user@foo.com",
        //        "criteria"    => "speaker:521",
        //        etc... using the same keys as the object variable names.
        // )

        // The BOOL variables confirmed and deleted will be true or false and will need to be
        // converted to 1/0 for MySQL.

        global $REMOTE_ADDR;

        $alerttime = gmdate("YmdHis");

        $criteria = alert_details_to_criteria($details);

        $q = $this->db->query("SELECT * FROM alerts WHERE email='" . mysqli_real_escape_string($this->db->conn, $details['email']) . "' AND criteria='" . mysqli_real_escape_string($this->db->conn, $criteria) . "' AND confirmed=1");
        if ($q->rows() > 0) {
            $deleted = $q->field(0, 'deleted');
            if ($deleted) {
                $this->db->query("UPDATE alerts SET deleted=0 WHERE email='" . $this->db->escape($details['email']) . "' AND criteria='" . $this->db->escape($criteria) . "' AND confirmed=1");
                return 1;
            }
            else {
                return -2;
            }
        }

        $sql = "INSERT INTO alerts (email, criteria, deleted, confirmed, recommended, created) ";
        $sql .= "VALUES (";
        $sql .= "'" . $this->db->escape($details["email"]) . "',";
        $sql .= "'" . $this->db->escape($criteria) . "', '0','0',";
        // MJ OA-437 add as recommendation.
        if ($details['recommended'] == 1) {
            $sql .= "'1',";
        }
        else {
            $sql .= "'0',";
        }
        $sql .= "NOW() )";

        $q = $this->db->query($sql);

        if ($q->success()) {

            // Get the alert id so that we can perform the updates for confirmation.

            $this->alert_id = $q->insert_id();
            $this->criteria = $criteria;

            // We have to set the alert's registration token.
            // This will be sent to them via email, so we can confirm they exist.
            // The token will be the first 16 characters of a crypt.

            // This gives a code for their email address which is then joined
            // to the timestamp so as to provide a unique ID for each alert.

            $token = substr(crypt($details["email"] . microtime()), 12, 16);

            // Full stops don't work well at the end of URLs in emails,
            // so replace them. We won't be doing anything clever with the crypt
            // stuff, just need to match this token.
            // Also, replace '/' with 'X' since two '//' in the url will get passed
            // to /alert/confirm with a single '/' for some reason.
            $this->registrationtoken = strtr($token, './', 'XX');

            // Add that to the database.

            $r = $this->db->query("UPDATE alerts
						SET registrationtoken = '" . mysqli_real_escape_string($this->db->conn, $this->registrationtoken) . "'
						WHERE alert_id = '" . mysqli_real_escape_string($this->db->conn, $this->alert_id) . "'
						");

            if ($r->success()) {
                // Updated DB OK.

                if ($confirmation_email) {
                    // Right, send the email...
                    $success = $this->send_confirmation_email($details);

                    if ($success) {
                        // Email sent OK.
                        return 1;
                    }
                    else {
                        // Couldn't send the email.
                        return -1;
                    }
                }
                elseif ($instantly_confirm) {
                    // No confirmation email needed.
                    $s = $this->db->query("UPDATE alerts
						SET confirmed = '1'
						WHERE alert_id = '" . mysqli_real_escape_string($this->db->conn, $this->alert_id) . "'
						");
                    return 1;
                }
            }
            else {
                // Couldn't add the registration token to the DB.
                return -1;
            }

        }
        else {
            // Couldn't add the user's data to the DB.
            return -1;
        }
    }

    /**
     * FUNCTION:  send_confirmation_email.
     */
    public function send_confirmation_email($details) {

        // After we've add()ed an alert we'll be sending them
        // a confirmation email with a link to confirm their address.
        // $details is the array we just sent to add(), and which it's
        // passed on to us here.
        // A brief check of the facts...
        if (
          !is_numeric($this->alert_id) ||
          !isset($details['email']) ||
          $details['email'] == ''
        ) {
            return FALSE;
        }

        // We prefix the registration token with the alert's id and '-'.
        // Not for any particularly good reason, but we do.

        $urltoken = $this->alert_id . '-' . $this->registrationtoken;

        $confirmurl = 'http://' . DOMAIN . WEBPATH . 'A/' . $urltoken;

        // Arrays we need to send a templated email.
        $data = [
          'to' => $details['email'],
          'template' => 'alert_confirmation'
        ];

        $merge = [
          'FIRSTNAME' => 'THEY WORK FOR YOU',
          'LASTNAME' => ' ALERT CONFIRMATION',
          'CONFIRMURL' => $confirmurl,
          'CRITERIA' => $this->criteria_pretty()
        ];

        $success = send_template_email($data, $merge);
        if ($success) {
            return TRUE;
        }
        else {
            return FALSE;
        }
    }

    /**
     * FUNCTION: email_exists.
     */
    public function email_exists($email) {
        // Returns true if there's a user with this email address.

        if ($email != "") {
            $q = $this->db->query("SELECT alert_id FROM alerts WHERE email='" . mysqli_real_escape_string($this->db->conn, $email) . "'");
            if ($q->rows() > 0) {
                return TRUE;
            }
            else {
                return FALSE;
            }
        }
        else {
            return FALSE;
        }

    }

    /**
     * FUNCTION: confirm.
     */
    public function confirm($token) {
        // The user has clicked the link in their confirmation email
        // and the confirm page has passed the token from the URL to here.
        // If all goes well the alert will be confirmed.
        // The alert will be active when scripts run each day to send the actual emails.

        // Split the token into its parts.
        if (strstr($token, '::')) {
            $arg = '::';
        }
        else {
            $arg = '-';
        }
        $token_parts = explode($arg, $token);
        if (count($token_parts) != 2) {
            return FALSE;
        }
        [$alert_id, $registrationtoken] = $token_parts;

        if (!is_numeric($alert_id) || $registrationtoken == '') {
            return FALSE;
        }

        $q = $this->db->query("SELECT email, criteria
						FROM alerts
						WHERE alert_id = '" . mysqli_real_escape_string($this->db->conn, $alert_id) . "'
						AND registrationtoken = '" . mysqli_real_escape_string($this->db->conn, $registrationtoken) . "'
						");

        if ($q->rows() == 1) {
            $this->criteria = $q->field(0, 'criteria');
            $this->email = $q->field(0, 'email');
            $r = $this->db->query("UPDATE alerts
						SET confirmed = '1', deleted = '0'
						WHERE	alert_id = '" . mysqli_real_escape_string($this->db->conn, $alert_id) . "'
						");

            if ($r->success()) {
                $this->confirmed = TRUE;
                return TRUE;
            }
            else {
                return FALSE;
            }
        }
        else {
            // Couldn't find this alert in the DB. Maybe the token was
            // wrong or incomplete?
            return FALSE;
        }
    }

    /**
     * FUNCTION:  delete.
     */
    public function delete($token) {
        // The user has clicked the link in their delete confirmation email
        // and the deletion page has passed the token from the URL to here.
        // If all goes well the alert will be flagged as deleted.

        // Split the token into its parts.
        if (strstr($token, '::')) {
            $arg = '::';
        }
        else {
            $arg = '-';
        }
        $bits = explode($arg, $token);
        if (count($bits) < 2) {
            return FALSE;
        }
        [$alert_id, $registrationtoken] = $bits;

        if (!is_numeric($alert_id) || $registrationtoken == '') {
            return FALSE;
        }

        $q = $this->db->query("SELECT email, criteria
						FROM alerts
						WHERE alert_id = '" . mysqli_real_escape_string($this->db->conn, $alert_id) . "'
						AND registrationtoken = '" . mysqli_real_escape_string($this->db->conn, $registrationtoken) . "'
						");

        if ($q->rows() == 1) {

            // Set that they're confirmed in the DB.
            $r = $this->db->query("UPDATE alerts
						SET deleted = '1'
						WHERE	alert_id = '" . mysqli_real_escape_string($this->db->conn, $alert_id) . "'
						");

            if ($r->success()) {

                $this->deleted = TRUE;
                return TRUE;

            }
            else {
                // Couldn't delete this alert in the DB.
                return FALSE;
            }

        }
        else {
            // Couldn't find this alert in the DB. Maybe the token was
            // wrong or incomplete?
            return FALSE;
        }
    }

    /**
     * Functions for accessing the user's variables.
     */
    public function alert_id() {
        return $this->alert_id;
    }

    /**
     *
     */
    public function email() {
        return $this->email;
    }

    /**
     *
     */
    public function criteria() {
        return $this->criteria;
    }

    /**
     *
     */
    public function criteria_pretty($html = FALSE) {
        $criteria = explode(' ', $this->criteria);
        $words = [];
        $spokenby = '';
        foreach ($criteria as $c) {
            if (preg_match('#^speaker:(\d+)#', $c, $m)) {
                $MEMBER = new MEMBER(['person_id' => $m[1]]);
                $spokenby = $MEMBER->full_name();
            }
            else {
                $words[] = $c;
            }
        }
        $criteria = '';
        if (count($words)) {
            $criteria .= ($html ? '<li>' : '* ') . 'Containing the ' . make_plural('word', count($words)) . ': ' . join(' ', $words) . ($html ? '</li>' : '') . "\n";
        }
        if ($spokenby) {
            $criteria .= ($html ? '<li>' : '* ') . "Spoken by $spokenby" . ($html ? '</li>' : '') . "\n";
        }
        return $criteria;
    }

    /**
     *
     */
    public function deleted() {
        return $this->deleted;
    }

    /**
     *
     */
    public function confirmed() {
        return $this->confirmed;
    }

    // PRIVATE FUNCTIONS BELOW... ////////////////.


} // End USER class
