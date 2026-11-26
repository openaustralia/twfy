<?php

/**
 * @file
 * We use this same text file for editing a user's details (both THEUSER's and
 */

// A different user's), and letting a new user join.
// This means we can keep all the form validation and display code in one place.

/* What happens?

Someone's own details are at /account/ (Laravel, AccountController), and nobody's details are public. What is
left here is joining, and an administrator editing another user, so:

1) We check what $this_page is going to be. This depends on:
 * the value of $pg, if anything;
 * whether $THEUSER has appropriate security privileges;
 * and whether we've been passed the ID of a user to edit.
Anything else goes to /account/.

2) If we've come to this page after submitting its form, we check the form data.
If the data is OK, we either edit or add the user.
If there were errors, the same form is displayed again with error messages.

3) On the other hand, if the form hasn't been submitted we've just arrived here.
In that case the form is displayed.


After the first part of working out which page we're on, and munging any data
various functions in this file are used:

check_input()   Validates the edited or added user data and creates error messages.
add_user()      Calls $THEUSER->add() and displays the results, depending on success.
update_user()   Calls the appropriate functions and updates, displays results.
display_form()  Displays the form for editing or adding a user.

 */


include_once __DIR__ . "/../../includes/easyparliament/init.php";
include_once __DIR__ . "/../../includes/easyparliament/member.php";

use OpenAustralia\TWFY\Account\LegacyUserPage;
use OpenAustralia\TWFY\Models\User as UserModel;
use OpenAustralia\TWFY\UserRoles;

// Which page we're on all depends on the value of the "pg" variable...
$pg = get_http_var("pg");

if ($pg == "join") {
    // A new user signing up.
    $this_page = "userjoin";

} elseif ($pg == "editother" && is_numeric(get_http_var("u")) && $THEUSER->is_able_to("edituser")) {
    // Editing someone else's info. We need a user_id, and the user must be allowed to do this.
    $this_page = "otheruseredit";

} else {
    // Someone's own details, which Laravel's pages show now. Nobody else's are shown, so there is nothing else here.
    header('Location: ' . WEBPATH . LegacyUserPage::destination($pg), true, 301);
    exit;
}


// A little detail... we want to change text in the page depending on whose
// info is being changed or added.
$who = $this_page == "otheruseredit" ? "the user's" : "your";


if (get_http_var("submitted") == "true" && $this_page != "userjoin" && !edit_token_is_valid()) {
    // Changing an existing user needs the token from the edit form, posted back here, so another site can't change a
    // user's details. Nothing the request sent is used or shown.
    http_response_code(403);

    $PAGE->page_start();

    $target_id = edit_target_id();
    if ($target_id !== null) {
        // Back to the edit page of the user who was being edited, as that is where the form is.
        $EDITURL = new URL('otheruseredit');
        $EDITURL->insert(['u' => (string) $target_id]);
        $PAGE->error_message([
            'text' => "Sorry, we couldn't check that this request came from your own form, so nothing was changed.",
            'linkurl' => $EDITURL->generate(),
            'linktext' => "Open the user's details and try again",
        ]);
    } else {
        $PAGE->error_message("Sorry, we couldn't check that this request came from your own form, so nothing was changed.");
    }

    $PAGE->page_end();

} elseif (get_http_var("submitted") == "true") {
    // The edit or join form has been submitted, so check input.

    // Put all the user-submitted data in an array.
    $details = [];
    $details["firstname"] = trim(get_http_var("firstname"));
    $details["lastname"] = trim(get_http_var("lastname"));
    $details["email"] = trim(get_http_var("em"));
    // We use boolean true/false internally. Convert the text from the form to boolean.
    $details["password"] = trim(get_http_var("password"));
    $details["password2"] = trim(get_http_var("password2"));
    $details["optin"] = get_http_var("optin") == "true" ? true : false;
    $details['mp_alert'] = get_http_var('mp_alert') == 'true' ? true : false;

    $details["constituency"] = trim(get_http_var("constituency"));

    if ($this_page == "otheruseredit") {
        $details["user_id"] = trim(get_http_var("u"));
        $details["status"] = trim(get_http_var("status"));
        if (get_http_var("deleted") != "") {
            $deleted = get_http_var("deleted");
            $details["deleted"] = $deleted[0] == "true" ? true : false;
        } else {
            $details['deleted'] = false;
        }
        if (get_http_var("confirmed") != "") {
            $confirmed = get_http_var("confirmed");
            $details["confirmed"] = $confirmed[0] == "true" ? true : false;
        } else {
            $details['confirmed'] = false;
        }
    }

    // Check the input.
    // If there are any errors with the submission, $errors (an array)
    // will have elements. The keys will be the name of form elements,
    // and the values will be text to display when we show the form again.
    $errors = check_input($details);

    if (count($errors) > 0) {
        // Validation errors. Print form again.

        $PAGE->page_start();

        display_form($details, $errors);

        $PAGE->page_end();

    } elseif ($this_page == "userjoin") {
        // No errors so far, so try to sign up and log in.

        add_user($details);

    } else {
        // No errors so far, editing an existing user,
        // $this_page == "otheruseredit".

        update_user($details);

    }


} else {
    // Someone has just arrived at this page, no form submitted.

    $PAGE->page_start();

    if ($this_page == "otheruseredit") {

        // We're editing the info of a different user.
        // So set up a new user object with the id supplied
        // and get the user's info.

        $USER = new USER();
        $USER->init(get_http_var("u"));

        $details = [];

        $details["user_id"] = $USER->user_id();
        $details["firstname"] = $USER->firstname();
        $details["lastname"] = $USER->lastname();
        $details["email"] = $USER->email();
        $details["password"] = $USER->password();
        $details["optin"] = $USER->optin();
        $details["constituency"] = $USER->constituency();
        $details["status"] = $USER->status();
        $details["deleted"] = $USER->deleted();
        $details["confirmed"] = $USER->confirmed();

        // Display the form with the other user's info.
        display_form($details);

    } else {

        // $this_page == "userjoin".
        // Display a blank form.
        display_form();

    }

    $PAGE->page_end();

}

/**
 * The id of the user being edited, when it is a whole number, or null.
 */
function edit_target_id(): ?int {
    $id = get_http_var("u");

    if (!is_string($id) || !ctype_digit($id)) {
        return null;
    }

    return (int) $id;
}

/**
 * The token the edit form carries, so another site can't change a signed-in user's details.
 *
 * Editing another user binds the token to that user, so one issued for one user is no good for another.
 * It is empty unless someone is signed in, as a token with no password hash to key it could be made by anyone.
 */
function edit_token(): string {
    global $THEUSER, $this_page;

    if (!$THEUSER->isloggedin() || (string) $THEUSER->password() === '') {
        return '';
    }

    $purpose = 'user-edit';
    if ($this_page == "otheruseredit") {
        $target_id = edit_target_id();
        if ($target_id === null) {
            return '';
        }
        $purpose .= ':' . $target_id;
    }

    return UserRoles::token((int) $THEUSER->user_id(), (string) $THEUSER->password(), $purpose);
}

/**
 * Whether the request posted the signed-in user's edit token.
 *
 * Only $_POST is read, as get_http_var() reads $_GET first and a token in a URL would end up in logs.
 */
function edit_token_is_valid(): bool {
    $token = edit_token();
    $posted = $_POST['token'] ?? '';

    return $token !== '' && is_string($posted) && hash_equals($token, $posted);
}

/**
 *
 */
function check_input($details) {
    global $THEUSER, $this_page, $who;

    // This may be a URL that will send the user back to where they were before they
    // wanted to join.
    $ret = get_http_var("ret");

    $errors = [];

    // Check each of the things the user has input.
    // If there is a problem with any of them, set an entry in the $errors array.
    // This will then be used to (a) indicate there were errors and (b) display
    // error messages when we show the form again.

    // People don't all have a first and a last name, so either one is enough.
    if ($details["firstname"] == "" && $details["lastname"] == "") {
        $errors["firstname"] = "Please enter $who first name or last name";
    }

    // Check email address is valid and unique.
    if ($details["email"] == "") {
        $errors["email"] = "Please enter $who email address";

    } elseif (!validate_email($details["email"])) {
        // validate_email() is in includes/utilities.php.
        $errors["email"] = "Please enter a valid email address";

    } elseif (UserModel::emailTaken($details["email"], $this_page == "otheruseredit" ? (int) $details["user_id"] : null)) {
        $errors["email"] = $this_page == "userjoin" ? "There is already a user with this email address" : "Someone else has already joined with this email address";
    }

    // Check passwords.
    if ($this_page == "userjoin") {

        // Only *must* enter a password if they're joining.
        if ($details["password"] == "") {
            $errors["password"] = "Please enter $who password";

        } elseif ($problem = UserModel::passwordProblem($details["password"])) {
            $errors["password"] = $problem;
        }

        if ($details["password2"] == "") {
            $errors["password2"] = "Please enter $who password again";
        }

        if ($details["password"] != "" && $details["password2"] != "" && $details["password"] != $details["password2"]) {
            $errors["password"] = ucfirst($who) . " passwords did not match. Please try again.";
        }

    } else {

        // Update details pages.

        if ($details["password"] != "" && $problem = UserModel::passwordProblem($details["password"])) {
            $errors["password"] = $problem;
        }

        if ($details["password"] != $details["password2"]) {
            $errors["password"] = ucfirst($who) . " passwords did not match. Please try again.";
        }
    }

    // No checking of URL.

    if ($this_page == "otheruseredit") {

        // We're editing another user's info.

        // Could check status here...?

    }

    // Send the array of any errors back...
    return $errors;
}

/**
 *
 */
function add_user($details) {
    global $THEUSER, $PAGE, $this_page;

    // If this goes well, the user will have their data
    // added to the database and a confirmation email
    // will be sent to them.
    $success = $THEUSER->add($details);

    if ($success) {
        // No validation errors.

        $this_page = 'userwelcome';

        $PAGE->page_start();

        $PAGE->stripe_start();

        $message = [
            'title' => "We're nearly done...",
            'text' => "You should receive an email shortly which will contain a link. You will need to follow that link to confirm your email address before you can log in. Thanks."
        ];

        $PAGE->message($message);

        $PAGE->stripe_end();

        /*      We used to log the user in straight away.
        Now we send them a confirmation email.

        Keeping this code here, just in case.
        Note that you'll probably have to add the 'remember' checkbox
        back into the sign-up form.


        // Does this user want to a long-term cookie?
        if (isset($details["remember"]) && $details["remember"]) {
        $expire = "never";
        } else {
        $expire ="session";
        }

        // Wherever the user ends up next, we'll display a welcome message to them
        // indicated by the 'newuser' element in the URL....

        // $ret might be a URL that will take the user back to where they
        // where before joining.
        if (get_http_var("ret") != "") {

        $url = get_http_var("ret");
        // We're now going to have to fudge things a bit. Want to add "newuser=1"
        // on to the end of wherever we were before, so a welcome message will
        // be displayed.
        if (preg_match("/\?.+/", $url)) {
        $url .= "&newuser=1";
        } else {
        $url .= "?newuser=1";
        }

        } else {
        // We'll send the user to the front page after they've joined.

        $URL = new URL("home");
        $URL->insert(array("newuser"=>"1"));
        $url = $URL->generate();
        }

        // Log the new user in. They'll be sent off elsewhere, so we don't output any
        // HTML here.
        $THEUSER->login($url, $expire);

         */

    } else {

        // Something went wrong, so display the form (with error messages).

        $this_page = 'userjoin';

        $PAGE->page_start();

        $errors["db"] = "Sorry, we were unable to create an account for you. Please <a href=\"mailto:" . CONTACTEMAIL . "\">let us know</a>. Thanks.";

        display_form($details, $errors);

    }

    $PAGE->page_end();
}

/**
 *
 */
function update_user($details) {
    global $THEUSER, $PAGE, $who;

    // There were no errors when the edit user form was submitted,
    // so make the changes in the DB.

    $success = $THEUSER->update_other_user($details);

    if ($success) {
        // No errors, all updated.

        $PAGE->page_start();

        $PAGE->stripe_start();

        $PAGE->message([
            'title' => 'Details updated',
            'text' => 'The details have been updated. <a href="' . WEBPATH . 'admin/users.php">Back to the users</a>.',
        ]);

        $PAGE->stripe_end();

        $PAGE->page_end();

    } else {
        // Something went wrong.

        $PAGE->page_start();

        $errors["db"] = "Sorry, we were unable to update $who details. Please <a href=\"mailto:" . CONTACTEMAIL . "\">let us know</a> what you were trying to change. Thanks.";

        display_form($details, $errors);

        $PAGE->page_end();
    }

}

/**
 *
 */
function display_form($details = [], $errors = []) {
    global $this_page, $THEUSER, $who, $PAGE;

    $PAGE->stripe_start();

    if (isset($errors["db"])) {

        $PAGE->error_message($errors["db"]);

    } else {

        $URL = new URL("userlogin");

        if (!$THEUSER->isloggedin()) {
            ?>
            <p>Already joined? <a href="<?php echo $URL->generate(); ?>">Then log in!</a></p>
            <?php
        }
    }

    $ACTIONURL = new URL($this_page);
    $ACTIONURL->reset();
    ?>

    <form method="post" action="<?php echo $ACTIONURL->generate(); ?>">
        <?php
        if ($this_page == "otheruseredit") {
            ?>
            <div class="row">
                <span class="label">User ID:</span>
                <span class="formw"><?php echo htmlentities($details["user_id"]); ?></span>
            </div>

            <?php
        }

        if (isset($errors["firstname"])) {
            $PAGE->error_message($errors["firstname"]);
        }
        ?>
        <div class="row">
            <span class="label"><label for="firstname">Your first name:</label></span>
            <span class="formw"><input type="text" name="firstname" id="firstname" value="<?php
            if (isset($details["firstname"])) {
                echo htmlentities($details["firstname"]);
            } ?>" maxlength="255" size="30" class="form"></span>
        </div>

        <?php
        if (isset($errors["lastname"])) {
            $PAGE->error_message($errors["lastname"]);
        }
        ?>
        <div class="row">
            <span class="label"><label for="lastname">Your last name:</label></span>
            <span class="formw"><input type="text" name="lastname" id="lastname" value="<?php
            if (isset($details["lastname"])) {
                echo htmlentities($details["lastname"]);
            } ?>" maxlength="255" size="30" class="form"></span>
        </div>

        <?php
        if (isset($errors["email"])) {
            $PAGE->error_message($errors["email"]);
        }
        ?>
        <div class="row">
            <span class="label"><label for="em">Email address:</label></span>
            <span class="formw"><input type="text" name="em" id="em" value="<?php
            if (isset($details["email"])) {
                echo htmlentities($details["email"]);
            } ?>" maxlength="255" size="30" class="form"></span>
        </div>

        <?php

        if ($this_page == "otheruseredit") {
            // If not, the user's joining.
            ?>
            <div class="row">
                &nbsp;<br><small>To change <?php echo $who; ?> password enter a new one twice below (otherwise, leave
                    blank).</small>
            </div>
            <?php
        }

        if (isset($errors["password"])) {
            $PAGE->error_message($errors["password"]);
        }
        ?>
        <div class="row">
            <span class="label"><label for="password">Password:</label></span>
            <span class="formw"><input type="password" name="password" id="password" value="" maxlength="30" size="20"
                    class="form"> <small>At least <?php echo UserModel::PASSWORD_MIN; ?> characters</small></span>
        </div>

        <?php
        if (isset($errors["password2"])) {
            $PAGE->error_message($errors["password2"]);
        }
        ?>
        <div class="row">
            <span class="label"><label for="password2">Repeat password:</label></span>
            <span class="formw"><input type="password" name="password2" id="password2" value="" maxlength="30" size="20"
                    class="form"></span>
        </div>


        <br style="clear: left;">&nbsp;<br>

        <span class="formw"><input type="hidden" name="constituency" id="constituency" value="<?php
            if (isset($details["constituency"])) {
            echo htmlentities($details["constituency"]);
            } ?>" maxlength="20" size="20" class="form"></span>
        <br>

        <div class="row">
            &nbsp;<br>Do <?php
            if ($this_page == "otheruseredit") {
                echo "they";
            } else {
                echo "you";
            } ?> wish to receive
            occasional update emails about OpenAustralia.org?
        </div>

        <?php
        if (isset($errors["optin"])) {
            $PAGE->error_message($errors["optin"]);
        }
        ?>
        <div class="row">
            <span class="label">&nbsp;</span>
            <span class="formw"><input type="radio" name="optin" id="optintrue" value="true" <?php
            if (!empty($details["optin"])) {
                print " checked";
            }
            ?>> <label
                    for="optintrue">Yes</label><br>
                <input type="radio" name="optin" id="optinfalse" value="false" <?php
                if (
                    ($this_page == "userjoin" && get_http_var("submitted") != "true")
                    ||
                    (isset($details["optin"]) && !$details["optin"])
                ) {
                    print " checked";
                }
                ?>> <label for="optinfalse">No</label></span>
        </div>

        <?php
        if ($this_page == 'userjoin') { ?>
            <div class="row">
                &nbsp;<br>Would <?php
                if ($this_page == "otheruseredit") {
                    echo "they";
                } else {
                    echo "you";
                } ?> like to
                receive an email whenever your MP does something in Parliament?
                <br /><small>&nbsp;&nbsp;(if you're already getting email alerts to your address, don't worry about
                    this)</small>
            </div>

            <div class="row">
                <span class="label">&nbsp;</span>
                <span class="formw"><input type="radio" name="mp_alert" id="mp_alerttrue" value="true" <?php
                if (!empty($details["mp_alert"])) {
                    print ' checked';
                }
                ?>> <label
                        for="mp_alerttrue">Yes</label><br>
                    <input type="radio" name="mp_alert" id="mp_alertfalse" value="false" <?php
                    if (
                        ($this_page == "userjoin" && get_http_var("submitted") != "true")
                        ||
                        (isset($details["mp_alert"]) && !$details["mp_alert"])
                    ) {
                        print ' checked';
                    }
                    ?>> <label for="mp_alertfalse">No</label></span>
            </div>
        <?php }

        if ($this_page == "otheruseredit") {

            if (isset($errors["status"])) {
                $PAGE->error_message($errors["status"]);
            }
            ?>
            <div class="row">
                <span class="label">Security status:</span>
                <span class="formw"><select name="status">
                        <?php
                        $USER = new USER();
                        $statuses = $USER->possible_statuses();
                        foreach ($statuses as $n => $status) {
                            print "\t<option value=\"$status\"";
                            if ($status == $details["status"]) {
                                print " checked";
                            }
                            print ">$status</option>\n";
                        }
                        ?>
                    </select></span>
            </div>

            <div class="row">
                <span class="label"><label for="confirmed">Confirmed?</label></span>
                <span class="formw"><input type="checkbox" name="confirmed[]" id="confirmed" value="true" <?php
                if (!empty($details["confirmed"])) {
                    print " checked";
                }
                ?>></span>
            </div>

            <div class="row">
                <span class="label"><label for="deleted">"Deleted"?</label></span>
                <span class="formw"><input type="checkbox" name="deleted[]" id="deleted" value="true" <?php
                if (!empty($details["deleted"])) {
                    print " checked";
                }
                ?>> <small>(No data will
                        actually be deleted.)</small></span>
            </div>

            <?php
        }

        if ($this_page == "otheruseredit") {
            $submittext = "Update details";
        } else {
            $submittext = "Join OpenAustralia.org";
        }

        $TERMSURL = new URL('houserules');
        ?>
        <div class="row">
            <span class="label">&nbsp;</span>
            <span class="formw">&nbsp;<br><small>Read our <a href="<?php echo $TERMSURL->generate(); ?>">Terms of
                        Use</a>.</small></span>
        </div>

        <div class="row">
            <span class="label">&nbsp;</span>
            <span class="formw"><input type="submit" class="submit"
                    value="<?php echo $submittext; ?>"><!-- this space makes the submit button appear on Mac IE 5! -->
            </span>
        </div>

        <input type="hidden" name="submitted" value="true">

        <?php
        if ($this_page == "otheruseredit") {
            // So another site can't submit this form for a signed-in user.
            ?>
            <input type="hidden" name="token" value="<?php echo htmlentities(edit_token()); ?>">
            <?php
        }

        if (get_http_var("ret") != "") {
            // The user was probably trying to add a writer but is joining first.
            ?>
            <input type="hidden" name="ret" value="<?php echo htmlentities(get_http_var("ret")); ?>">
            <?php
        }

        if (get_http_var("pg") != "") {
            // So that we get to the right page.
            ?>
            <input type="hidden" name="pg" value="<?php echo htmlentities(get_http_var("pg")); ?>">
            <?php
        }

        if ($this_page == "otheruseredit") {
            // Need to store the id of the user we're editing.
            ?>
            <input type="hidden" name="u" value="<?php echo htmlentities($details["user_id"]); ?>">
            <?php
        }
        ?>

    </form>
    <?php

    if ($this_page == 'userjoin') {

        $PAGE->stripe_end([
            [
                'type' => 'include',
                'content' => 'userjoin'
            ]
        ]);

    } else {
        $PAGE->stripe_end();
    }

} // End display_form()
