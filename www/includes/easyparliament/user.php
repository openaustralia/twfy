<?php

/**
 * @file
 * NO HTML IN THIS FILE!!
 *
 * This file contains USER and THEUSER classes.
 *
 * It automatically instantiates a $THEUSER global object. This refers to the person actually viewing the site. If they have a valid cookie set, $THEUSER's data will be fetched from the DB and they will be logged in. Otherwise they will have a minimum access level and will not be logged in.
 *
 *
 * The USER class allows us to fetch and alter data about any user (rather than $THEUSER).
 *
 * To create a new user do:
 * $USER = new USER;
 * $USER->init($user_id);
 *
 * You can then access all the user's variables with appropriately named functions, such as:
 * $USER->user_id();
 * $USER->email();
 * etc. Don't access the variables directly because I think that's bad.
 *
 *
 *
 * USER is extended into the THEUSER class which is used only for the person currently using the site. ie, it adds functions for logging in and out, checking log in status, etc.
 *
 *
 * GUESTUSER:
 * In the database there should be a user with an id of 0 and a status of 'Viewer' (and probably a name of 'Guest').
 *
 *
 *
 * The cookie set to indicate a logged in user is called "epuser_id". More on that in THEUSER().
 *
 *
 * Functions here:
 *
 * USER
 * init()                Send it a user id to fetch data from DB.
 * add()                Add a new user to the DB.
 * send_confirmation_email()    Done after add()ing the user.
 * update_other_user()    Update the data of another user.
 * change_password()    Generate a new password and put in DB.
 * id_exists()            Checks if a user_id is valid.
 * email_exists()        Checks if a user exists with a certain email address.
 * is_able_to()        Is the user allowed to perform this action?
 * possible_statuses()    Return an array of the possible security statuses for users.
 * Accessor functions for each object variable (eg, user_id()  ).
 * _update()            Private function that updates a user's data in DB.
 *
 * THEUSER
 * THEUSER()            Constructor that logs in if the cookie is correct.
 * isloggedin()        Check if the user is logged in or not.
 * isvalid()            Check to see if the user's login form details are OK.
 * login()                Log the user in.
 * logout()            Log the user out.
 * confirm()            With the correct token, confirms the user then logs them in.
 * update_self()        Update the user's own data in the DB.
 */

require_once __DIR__ . '/../request.php';

use Illuminate\Database\Capsule\Manager as DB;
use OpenAustralia\TWFY\Models\User as UserModel;

/**
 *
 */
class USER {


    /**
     * So we have an ID for non-logged in users reporting comments etc.
     */
    public $user_id = "0";
    public $firstname = "Guest";    /**
                                     * So we have something to print for non-logged in users.
                                     */
    public $lastname = "";
    /**
     * This will be a crypt()ed version of a plaintext pw.
     */
    public $password = "";
    public $email = "";
    /**
     * Boolean - can other users see this user's email?
     */
    public $emailpublic = "";
    public $constituency = "";
    public $url = "";
    /**
     * Last time the logged-in user loaded a page (GMT).
     */
    public $lastvisit = "";
    public $registrationtime = "";    /**
                                       * When they registered (GMT).
                                       */
    public $registrationip = "";    /**
                                     * Where they registered from.
                                     */
    /**
     * Boolean - Do they want emails from us?
     */
    public $optin = "";
    public $deleted = "";            /**
                                      * User can't log in or have their info displayed.
                                      */
    /**
     * Boolean - Has the user confirmed via email?
     */
    public $confirmed = '';
    /**
     * Don't use the status to check access privileges - use the is_able_to() function.
     */
    public $status = "Viewer";
    public $registrationtoken = "";

    // If you add more user variables above you should also:
    // * Add the appropriate code to $this->add()
    // * Add the appropriate code to $this->_update()
    // * Add accessor functions way down below...
    // * Alter THEUSER->update_self() to update with the new vars, if appropriate.

    /**
     * Change things in the add/edit/view user page.
     */
    public function __construct() {
    }

    /**
     *
     */
    public function init(int $user_id): bool {
        // Pass it a user id and it will fetch the user's data from the db
        // and put it all in the appropriate variables.
        // Returns true if we've found user_id in the DB, false otherwise.

        $user = UserModel::find($user_id);

        if ($user) {
            // We've got a user, so set them up.

            $this->user_id = $user_id;
            $this->firstname = $user->firstname;
            $this->lastname = $user->lastname;
            $this->password = $user->password;
            $this->email = $user->email;
            $this->emailpublic = $user->emailpublic == 1 ? true : false;
            $this->constituency = $user->constituency;
            $this->url = $user->url;
            $this->lastvisit = $user->lastvisit;
            $this->registrationtime = $user->registrationtime;
            $this->registrationip = $user->registrationip;
            $this->optin = $user->optin == 1 ? true : false;
            $this->status = $user->status;
            $this->deleted = $user->deleted == 1 ? true : false;
            $this->confirmed = $user->confirmed == 1 ? true : false;

            return true;

        } else {
            return false;
        }

    }

    /**
     *
     */
    public function add(array $details, bool $confirmation_required = true): bool {
        // Adds a new user's info into the db.
        // Then optionally (and usually) calls another function to
        // send them a confirmation email.

        /*
        $details is an associative array of all the user's details, of the form:
        array (
            "firstname" => "Fred",
            "lastname"    => "Bloggs",
            etc... using the same keys as the object variable names.
        )
        */

        // The BOOL variables (eg, optin) will be true or false and will need to be
        // converted to 1/0 for MySQL.

            $registrationtime = gmdate("Y-m-d H:i:s");

        // Hash password for storage using bcrypt (PASSWORD_DEFAULT).
        // This is different to legacy md5-crypt hashes from the PHP 5.x era which has `$1$` prefix.
        $passwordforDB = password_hash($details["password"], PASSWORD_DEFAULT);

        if (!isset($details["status"])) {
            $details["status"] = "User";
        }

        $optin = !empty($details["optin"]) ? 1 : 0;

        $emailpublic = !empty($details["emailpublic"]) ? 1 : 0;

        try {
            $user = UserModel::create([
                'firstname' => $details["firstname"],
                'lastname' => $details["lastname"],
                'email' => $details["email"],
                'emailpublic' => $emailpublic,
                'constituency' => $details["constituency"],
                'url' => $details["url"],
                'password' => $passwordforDB,
                'optin' => $optin,
                'status' => $details["status"],
                'registrationtime' => $registrationtime,
                'registrationip' => ip_address(),
                'deleted' => 0,
                'confirmed' => 0,
            ]);

            // Set these so we can log in.
            // Except we no longer automatically log new users in, we
            // send them an email. So this may not be required.
            $this->user_id = $user->user_id;
            $this->password = $passwordforDB;

            // We have to generate the user's unique registration token.
            // This will be sent to them via email, so we can confirm they exist.
            // The token will be 22 characters of a random base63 string.

            // This gives a code for their email address to provide a unique ID for each email confirmation.

            // We upgraded from 16 to 22 chars in the 2026 move to php 8.0 (still fits in varchar(34) DB field)
            // and use cryptographically secure random bytes;
            // both +/ are changed to '_' as we use '-' elsewhere as a separator so it's technically base63.
            $token = rtrim(strtr(base64_encode(random_bytes(16)), '+/', '__'), '=');

            // We won't be doing anything clever with the crypt stuff, just need to match this token.
            $this->registrationtoken = $token;

            // Add that to the DB.
            $user->update(['registrationtoken' => $this->registrationtoken]);

            if ($details['mp_alert'] && $details['constituency']) {
                $MEMBER = new MEMBER(['constituency' => $details['constituency']]);
                $pid = $MEMBER->person_id();
                // No confirmation email, but don't automatically confirm.
                $ALERT = new ALERT();
                $ALERT->add([
                    'email' => $details['email'],
                    'pid' => $pid
                ], false, false);
            }

            if ($confirmation_required) {
                // Right, send the email...
                $success = $this->send_confirmation_email($details);

                if ($success) {
                    // All is good in the world!
                    return true;
                } else {
                    // Couldn't send the email.
                    return false;
                }
            } else {
                // No confirmation email needed.
                return true;
            }
        } catch (\Exception $e) {
            // Couldn't add the user's data to the DB.
            return false;
        }
    }

    /**
     *
     */
    public function send_confirmation_email($details) {
        // After we've add()ed a user we'll probably be sending them
        // a confirmation email with a link to confirm their address.

        // $details is the array we just sent to add(), and which it's
        // passed on to us here.

        // A brief check of the facts...
        if (
            !is_numeric($this->user_id) ||
            !isset($details['email']) ||
            $details['email'] == ''
        ) {
            return false;
        }

        // We prefix the registration token with the user's id and '-'.
        // Not for any particularly good reason, but we do.

        $urltoken = $this->user_id . '-' . $this->registrationtoken;

        $confirmurl = 'https://' . DOMAIN . WEBPATH . 'U/' . $urltoken;

        // Arrays we need to send a templated email.
        $data = [
            'to' => $details['email'],
            'template' => 'join_confirmation'
        ];

        $merge = [
            'FIRSTNAME' => $details['firstname'],
            'LASTNAME' => $details['lastname'],
            'CONFIRMURL' => $confirmurl
        ];

        $success = send_template_email($data, $merge);

        if ($success) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     */
    public function update_other_user($details) {
        // If someone (like an admin) is updating another user, call this
        // function. It checks their privileges before letting them.

        // $details is an array like that in $this->add().
        // It must include a 'user_id' element!

        global $THEUSER;

        if (!isset($details["user_id"])) {
            return false;

        } elseif ($THEUSER->is_able_to("edituser")) {

            // If the user doing the updating has appropriate privileges...

            $newdetails = $this->_update($details);

            // $newdetails will be an array of details if all went well,
            // false otherwise.
            if ($newdetails) {
                return true;
            } else {
                return false;
            }

        } else {

            return false;

        }
    }

    /**
     *
     */
    public function change_password(string $email) {

        // This function is called from the Change Password page.
        // It will create a new password for the user with $email address.
        // If all goes OK it will return the plaintext version of the password.
        // Otherwise it returns false.

        $user = UserModel::where('email', $email)->first(['user_id']);

        if (!$user) {
            // Email didn't exist.
            return false;
        }

        $this->email = $email;
        // Generates an unambiguous 14-character cryptographically secure random password (up from 6 uppercase).
        // FIXME: Replace with a token based password replacement with expiry.
        $unambiguous_alphabet = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789';
        $last_index = strlen($unambiguous_alphabet) - 1;
        $pwd = '';
        for ($i = 0; $i < 14; $i++) {
            $pwd .= $unambiguous_alphabet[random_int(0, $last_index)];
        }

        $passwordforDB = password_hash($pwd, PASSWORD_DEFAULT);

        $updated = $user->update([
            'password' => $passwordforDB,
        ]);

        if ($updated === false) {
            return false;
        }

        $this->password = $pwd;
        return $pwd;

    }

    /**
     *
     */
    public function send_password_reminder() {
        global $PAGE;

        // You'll probably have just called $this->change_password().

        if ($this->email() == '') {
            $PAGE->error_message("No email set for this user, so can't send a password reminder.");
            return false;
        }

        $data = [
            'to' => $this->email(),
            'template' => 'new_password'
        ];

        $URL = new URL("userlogin");

        $merge = [
            'EMAIL' => $this->email(),
            'LOGINURL' => "https://" . DOMAIN . $URL->generate(),
            'PASSWORD' => $this->password()
        ];

        // send_template_email in utility.php.
        $success = send_template_email($data, $merge);

        return $success;

    }

    /**
     *
     */
    public function id_exists(int $user_id): bool {
        // Returns true if there's a user with this user_id.

        return UserModel::where('user_id', $user_id)->exists();

    }

    /**
     *
     */
    public function email_exists(string $email): bool {
        // Returns true if there's a user with this email address.

        return $email !== '' && UserModel::where('email', $email)->exists();

    }

    /**
     *
     */
    public function is_able_to($action) {
        // Call this function to find out if a user is allowed to do something.
        // It uses the user's status to return true or false.
        // Possible actions:
        // "addcomment"
        // "reportcomment"
        // "edituser".
        global $PAGE;

        $status = $this->status();

        switch ($action) {

            // You can add more below as they're needed...
            // But keep them in alphabetical order!

            // Post comments.
            case "addcomment":

                switch ($status) {
                    case "User":
                      return true;

                    case "Moderator":
                      return true;

                    case "Administrator":
                      return true;

                    case "Superuser":
                      return true;

                    case "Viewer":
                      return false;
                }
              return false;

            // Add Glossary terms.
            case "addterm":
                switch ($status) {
                    case "User":
                      return true;

                    case "Moderator":
                      return true;

                    case "Administrator":
                      return true;

                    case "Superuser":
                      return true;

                    case "Viewer":
                      return false;
                }
              return false;

            // Delete comments.
            case "deletecomment":

                switch ($status) {
                    case "User":
                      return false;

                    case "Moderator":
                      return true;

                    case "Administrator":
                      return true;

                    case "Superuser":
                      return true;

                    case "Viewer":
                      return false;
                }
              return false;

            case "edituser":

                switch ($status) {
                    case "User":
                      return false;

                    case "Moderator":
                      return false;

                    case "Administrator":
                      return false;

                    case "Superuser":
                      return true;

                    case "Viewer":
                      return false;
                }
              return false;

            // Report a comment for moderation.
            case "reportcomment":
                switch ($status) {
                    case "User":
                      return true;

                    case "Moderator":
                      return true;

                    case "Administrator":
                      return true;

                    case "Superuser":
                      return true;

                    case "Viewer":
                      return true;
                }
              return true;

            // Access pages in the Admin section.
            case "viewadminsection":

                switch ($status) {
                    case "User":
                      return false;

                    case "Moderator":
                      return false;

                    case "Administrator":
                      return true;

                    case "Superuser":
                      return true;

                    case "Viewer":
                      return false;
                }
              return false;

            // Rate hansard things interesting/not.
            case "voteonhansard":
                /* Everyone */
              return true;

            default:
                $PAGE->error_message("You need to set permissions for '$action'!");
              return false;

        }

    }

    // Same for every user...
    // Just returns an array of the possible statuses a user could have.

    /**
     * Handy for forms where you edit/view users etc.
     */
    public function possible_statuses() {
        // Maybe there's a way of fetching these from the DB,
        // so we don't duplicate them here...?

        $statuses = ["Viewer", "User", "Moderator", "Administrator", "Superuser"];

        return $statuses;

    }

    /**
     * Functions for accessing the user's variables.
     */
    public function user_id() {
        return $this->user_id;
    }

    /**
     *
     */
    public function firstname() {
        return $this->firstname;
    }

    /**
     *
     */
    public function lastname() {
        return $this->lastname;
    }

    /**
     *
     */
    public function password() {
        return $this->password;
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
    public function emailpublic() {
        return $this->emailpublic;
    }

    /**
     *
     */
    public function constituency() {
        return $this->constituency;
    }

    /**
     *
     */
    public function url() {
        return $this->url;
    }

    /**
     *
     */
    public function lastvisit() {
        return $this->lastvisit;
    }

    /**
     *
     */
    public function registrationtime() {
        return $this->registrationtime;
    }

    /**
     *
     */
    public function registrationip() {
        return $this->registrationip;
    }

    /**
     *
     */
    public function optin() {
        return $this->optin;
    }

    // Don't use the status to check access privileges - use the is_able_to() function.
    // But you might use status() to return text to display, describing a user.
    // We can then change what status() does in the future if our permissions system.

    /**
     * Changes.
     */
    public function status() {
        return $this->status;
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

    /**
     *
     */
    public function constituency_is_set() {
        if ($this->constituency != '') {
            return true;
        } else {
            return false;
        }
    }

    /**
     * PRIVATE FUNCTIONS BELOW... ////////////////.
     */
    public function _update($details) {
        // Update a user's info.
        // DO NOT call this function direct.
        // Call either $this->update_other_user() or $this->update_self().

        // $details is an array like that in $this->add().
        global $PAGE;

        // Update email alerts if email address changed.
        if ($this->email != $details['email']) {
            DB::table('alerts')->where('email', $this->email)->update(['email' => $details['email']]);
        }

        $update_data = [];

        if (isset($details["password"]) && $details["password"] != "") {
            // The password is being updated.
            // If not, the password fields on the form will be left blank
            // so we don't want to overwrite the user's pw in the DB!

            // Hash password for storage using bcrypt (PASSWORD_DEFAULT).
            // Different to legacy md5-crypt hashes from the PHP 5.x era which has `$1$` prefix.
            $passwordforDB = password_hash($details["password"], PASSWORD_DEFAULT);

            $update_data['password'] = $passwordforDB;
        }

        if (isset($details["deleted"])) {
            // 'deleted' won't always be an option (ie, if the user is updating
            // their own info).
            $update_data['deleted'] = $details['deleted'] ? 1 : 0;
        }

        if (isset($details["confirmed"])) {
            // 'confirmed' won't always be an option (ie, if the user is updating
            // their own info).
            $update_data['confirmed'] = $details['confirmed'] ? 1 : 0;
        }

        if (isset($details["status"]) && $details["status"] != "") {
            // 'status' won't always be an option (ie, if the user is updating
            // their own info.
            $update_data['status'] = $details["status"];
        }

        // Convert internal true/false variables to MySQL BOOL 1/0 variables.
        $emailpublic = !empty($details["emailpublic"]) ? 1 : 0;
        $optin = !empty($details["optin"]) ? 1 : 0;

        $update_data += [
            'firstname' => $details["firstname"],
            'lastname' => $details["lastname"],
            'email' => $details["email"],
            'emailpublic' => $emailpublic,
            'constituency' => $details["constituency"],
            'url' => $details["url"],
            'optin' => $optin,
        ];

        // If we're returning to
        // $this->update_self() then $THEUSER will have its variables
        // updated if everything went well.
        try {
            UserModel::where('user_id', $details["user_id"])->update($update_data);
            return $details;
        } catch (\Exception $e) {
            $PAGE->error_message("Sorry, we were unable to update user id '" . htmlentities($details["user_id"]) . "'");
            return false;
        }

    }

}
/**
 * End USER class.
 */

/**
 * Handles all the login/out functionality and checking for the user
 * who is using the site right NOW. Yes, them, over there.
 */
class THEUSER extends USER {

    /**
     * This will become true if all goes well...
     */
    public $loggedin = false;

    /**
     *
     */
    public function __construct() {
        // This function is run automatically when a THEUSER
        // object is instantiated.

        // Set up $this->db.
        parent::__construct();

        // We look at the user's cookie and see if it's valid.
        // If so, we're going to log them in.

        // A user's cookie is of the form:
        // 123.blahblahblah
        // Where '123' is a user id, and 'blahblahblah' is an md5 hash of the
        // encrypted password we've stored in the db.
        // (Maybe we could just put the encrypted pw in the cookie and md5ing
        // it is overkill? Whatever, it works.)

        // In includes/utility.php.
        $cookie = get_cookie_var("epuser_id");

        if ($cookie == '') {
            twfy_debug("THEUSER init FAILED", "No cookie set");
            $this->loggedin = false;

        } elseif (preg_match("/([[:alnum:]]*)\.([[:alnum:]]*)/", $cookie, $matches)) {

            if (is_numeric($matches[1])) {

                $success = $this->init($matches[1]);

                if ($success) {
                    // We got all the user's data from the DB.

                    // But we need to check the password before we log them in.
                    // And make sure the user hasn't been "deleted".

                    if (md5($this->password()) == $matches[2] && !$this->deleted()) {
                        // The correct password is in the cookie,
                        // and the user isn't deleted, so set the user to be logged in.

                        // This would be an appropriate place to call other functions
                        // that might set user info that only a logged-in user is going
                        // to need. Their preferences and saved things or something.

                        twfy_debug("THEUSER init SUCCEEDED", "setting as logged in");
                        $this->loggedin = true;

                    } elseif (md5($this->password()) != $matches[2]) {
                        twfy_debug("THEUSER init FAILED", "Password doesn't match cookie");
                        $this->loggedin = false;
                    } else {
                        twfy_debug("THEUSER init FAILED", "User is deleted");
                        $this->loggedin = false;
                    }

                } else {
                    twfy_debug("THEUSER init FAILED", "didn't get 1 row from db");
                    $this->loggedin = false;
                }

            } else {
                twfy_debug("THEUSER init FAILED", "cookie's user_id is not numeric");
                $this->loggedin = false;
            }

        } else {
            twfy_debug("THEUSER init FAILED", "cookie is not of the correct form");
            $this->loggedin = false;
        }

        // If a user is logged in they *might* have set their own constituency.
        // If they aren't logged in, or they haven't set one, then we may
        // have set a constituency for them when they searched for their MP.
        // If so, we'll use that as $this->consitutuency.
        if ($this->constituency == '') {
            if (get_cookie_var(CONSTITUENCY_COOKIE) != '') {
                $constituency = get_cookie_var(CONSTITUENCY_COOKIE);
                $this->set_constituency_cookie($constituency);
            }
        }

        $this->update_lastvisit();

    } // End THEUSER()

    /**
     *
     */
    public function update_lastvisit() {

        if ($this->isloggedin()) {
            // Set last_visit to now.
            $date_now = gmdate("Y-m-d H:i:s");
            UserModel::where('user_id', $this->user_id())->update([
                'lastvisit' => $date_now,
            ]);

            $this->lastvisit = $date_now;
        }
    }

    // For completeness, but it's better to call $this->isloggedin()

    /**
     * If you want to check the log in status.
     */
    public function loggedin() {
        return $this->loggedin;
    }

    /**
     *
     */
    public function isloggedin() {
        // Call this function to check if the user is successfully logged in.

        if ($this->loggedin()) {
            twfy_debug("THEUSER", "isloggedin: true");
            return true;
        } else {
            twfy_debug("THEUSER", "isloggedin: false");
            return false;
        }
    }

    /**
     *
     */
    public function isvalid(string $email, string $userenteredpassword) {
        // Returns true if this email and plaintext password match a user in the db.
        // If false returns an array of form error messages.

        // We use this on the log in page to check if the details the user entered
        // are correct. We can then continue with logging the user in (taking into
        // account their cookie remembering settings etc) with $this->login().

        $user = UserModel::where('email', $email)
          ->first(['user_id', 'password', 'deleted', 'confirmed']);

        if ($user) {
            // OK.
            // The password in the DB is crypted.
            $dbpassword = $user->password;
            if (str_starts_with($dbpassword, '$1$')) {
                // Legacy md5-crypt hash from PHP 5.x era
                // FIXME: remove once count is zero: `select count(*) from users where password like '$1$%';`.
                $valid_password = crypt($userenteredpassword, $dbpassword) === $dbpassword;

                if ($valid_password) {
                    // Upgrade to the more secure Bcrypt hash.
                    $newHash = password_hash($userenteredpassword, PASSWORD_DEFAULT);
                    $user->update(['password' => $newHash]);
                    $dbpassword = $newHash;
                }
            } else {
                // Modern bcrypt hash from move to php 8.0 in 2026.
                $valid_password = password_verify($userenteredpassword, $dbpassword);
            }
            if ($valid_password) {
                $this->user_id = $user->user_id;
                $this->password = $dbpassword;
                // We'll need these when we're going to log in.
                $this->deleted = $user->deleted == 1 ? true : false;
                $this->confirmed = $user->confirmed == 1 ? true : false;
                return true;

            } else {
                // Failed.
                return ["invalidpassword" => "This is not the correct password for " . htmlentities($email)];

            }

        } else {
            // Failed.
            return ["invalidemail" => "There is no user registered with an email of " . htmlentities($email) . '. If you are subscribed to email alerts, you are not necessarily registered on the website. If you register, you will be able to manage your email alerts, as well as leave comments.'];
        }

    }

    /**
     * Validate that a redirect URL is safe.
     *
     * Only site-relative URLs are allowed.
     */
    private function is_safe_redirect_url($url) {
        // Relative URLs (starting with /) are safe.
        if (strpos($url, '/') === 0) {
            return true;
        }

        return false;
    }

    /**
     * This is used to log the user in. Duh.
     * You should already have checked the user's email and password using
     * $this->isvalid()
     * That will have set $this->user_id and $this->password, allowing the
     * login to proceed...
     *
     * $expire is either 'session' or 'never' - for the cookie.
     *
     * $returl is the URL to redirect the user to after log in, generally the
     * page they were on before. But if it doesn't exist, they'll just go to
     * the front page.
     */
    public function login(string $returl = "", $expire = "session") {

        global $PAGE;

        if ($returl == "" || !$this->is_safe_redirect_url($returl)) {
            $URL = new URL("home");
            $returl = $URL->generate();
        }

        // Various checks about the user - if they fail, we exit.
        if ($this->user_id() == "" || $this->password == "") {
            $PAGE->error_message("We don't have the user_id or password to make the cookie.", true);
            return;
        } elseif ($this->deleted) {
            $PAGE->error_message("This user has been deleted.", true);
            return;
        } elseif (!$this->confirmed) {
            $PAGE->error_message("this user has not been confirmed yet.", true);
            return;
        }

        // Unset any existing constituency cookie.
        // This will be the constituency the user set for themselves as a non-logged-in
        // user. We don't want it hanging around as it causes confusion.
        $this->unset_constituency_cookie();

        // Reminder: $this->password is actually a crypted version of the plaintext pw.
        $cookie = $this->user_id() . "." . md5($this->password());

        if ($expire == 'never') {
            header("Location: $returl");
            setcookie('epuser_id', $cookie, time() + 86400 * 365 * 20, '/', COOKIEDOMAIN);
        } else {
            header("Location: $returl");
            setcookie('epuser_id', $cookie, 0, '/', COOKIEDOMAIN);
        }
    }

    /**
     *
     */
    public function logout(string $returl) {

        // $returl is the URL to redirect the user to after log in, generally the
        // page they were on before. But if it doesn't exist, they'll just go to
        // the front page.

        if ($returl == '' || !$this->is_safe_redirect_url($returl)) {
            $URL = new URL("home");
            $returl = $URL->generate();
        }

        // get_cookie_var() is in includes/utility.php.
        if (get_cookie_var("epuser_id") != "") {
            // They're logged in, so set the cookie to empty.
            header("Location: $returl");
            setcookie('epuser_id', '', time() - 86400, '/', COOKIEDOMAIN);
        }
    }

    /**
     * The user has clicked the link in their confirmation email
     * and the confirm page has passed the token from the URL to here.
     * If all goes well they'll be confirmed and then logged in.
     */
    public function confirm(string $token) {

        // Split the token into its parts.
        $arg = '';
        if (strstr($token, '::')) {
            $arg = '::';
        }
        if (strstr($token, '-')) {
            $arg = '-';
        }
        [$user_id, $registrationtoken] = explode($arg, $token);

        if (!is_numeric($user_id) || $registrationtoken == '') {
            return false;
        }

        $user = UserModel::where('user_id', $user_id)->where('registrationtoken', $registrationtoken)->first(['user_id', 'email', 'password', 'constituency']);

        if (!$user) {
                // Couldn't find this user in the DB. Maybe the token was
                // wrong or incomplete?
                return false;
        }

        // We'll need these to be set before logging the user in.
        $this->user_id = $user_id;
        $this->email = $user->email;
        $this->password = $user->password;

        // Set that they're confirmed in the DB.
        if ($user->update(['confirmed' => 1]) === false) {
            // Couldn't set them as confirmed in the DB.
            twfy_debug('THEUSER confirm FAILED', "Could not set confirmed for user_id $user_id");

            return false;
        }

        if ($user->constituency) {
            $MEMBER = new MEMBER(['constituency' => $user->constituency]);
            $pid = $MEMBER->person_id();
            // This should probably be in the ALERT class.
            DB::table('alerts')
              ->where('email', $this->email)
              ->where('criteria', 'speaker:' . $pid)
              ->update(['confirmed' => 1]);
        }

        $this->confirmed = true;

        // Log the user in, redirecting them to the confirm page
        // where they should get a nice welcome message.
        $URL = new URL('userconfirmed');
        $URL->insert(['welcome' => 't']);
        $redirecturl = $URL->generate();

        $this->login($redirecturl, 'session');

    }

    /**
     *
     */
    public function set_constituency_cookie($constituency) {
        $this->constituency = $constituency;
        // If in debug mode.
        if (!headers_sent()) {
            setcookie(CONSTITUENCY_COOKIE, $constituency, time() + 7 * 86400, "/", COOKIEDOMAIN);
        }
        twfy_debug('USER', "Set the cookie named '" . CONSTITUENCY_COOKIE . " to '$constituency' for " . COOKIEDOMAIN . " domain");
    }

    /**
     *
     */
    public function unset_constituency_cookie() {
        // If in debug mode.
        if (!headers_sent()) {
            setcookie(CONSTITUENCY_COOKIE, '', time() - 3600, '/', COOKIEDOMAIN);
        }
    }

    /**
     *
     */
    public function update_self($details) {
        // If the user wants to update their details, call this function.
        // It checks that they're logged in before letting them.

        // $details is an array like that in $this->add().

        global $THEUSER;

        if ($this->isloggedin()) {

            $details["user_id"] = $this->user_id;

            $newdetails = $this->_update($details);

            // $newdetails will be an array of details if all went well,
            // false otherwise.

            if ($newdetails) {
                // The user's data was updated, so we'll change the object
                // variables accordingly.

                $this->firstname = $newdetails["firstname"];
                $this->lastname = $newdetails["lastname"];
                $this->email = $newdetails["email"];
                $this->emailpublic = $newdetails["emailpublic"];
                $this->constituency = $newdetails["constituency"];
                $this->url = $newdetails["url"];
                $this->optin = $newdetails["optin"];
                if (array_key_exists("password", $newdetails) && $newdetails["password"] != "") {
                    $this->password = $newdetails["password"];
                }

                return true;
            } else {
                return false;
            }

        } else {
            return false;
        }

    }

}

// Yes, we instantiate a new global $THEUSER object when every page loads.
$THEUSER = new THEUSER();
