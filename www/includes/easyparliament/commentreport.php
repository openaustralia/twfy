<?php

/**
 * Comment reports are when a user complains about a comment.
 * A report is logged and an admin user can then either approve or reject
 * the report. If they approve, the associated comment is deleted.
 *
 * To create a new comment report:
 * $REPORT = new COMMENTREPORT;
 * $REPORT->create($data);
 *
 * To view info about an existing report:
 * $REPORT = new COMMENTREPORT($report_id);
 * $REPORT->display();
 *
 * You can also do $REPORT->lock() and $REPORT->unlock() to ensure only
 * one person can process a report at a time.
 *
 * And finally you can $REPORT->resolve() to approve or reject the report.
 */
class COMMENTREPORT {

    private $db = NULL;

    public $report_id = '';
    public $comment_id = '';
    public $firstname = '';
    public $lastname = '';
    public $body = '';
    /**
     * Datetime.
     */
    public $reported = NULL;
    /**
     * Datetime.
     */
    public $resolved = NULL;
    public $resolvedby = '';     /**
                                  * User_id.
                                  */
    /**
     * Datetime.
     */
    public $locked = NULL;
    public $lockedby = '';        /**
                                   * User_id.
                                   */
    /**
     * Boolean.
     */
    public $upheld = '';

    /**
     * If the user was logged in, this will be set:
     */
    public $user_id = '';
    /**
     * If the user wasn't logged in, this will be set:
     */
    public $email = '';

    /**
     *
     */
    public function __construct($report_id = '') {
        // Pass it a report id and it gets and sets this report's data.

        $this->db = new ParlDB();

        if (is_numeric($report_id)) {

            $q = $this->db->query("SELECT commentreports.comment_id,
									commentreports.user_id,
									commentreports.body,
									DATE_FORMAT(commentreports.reported, '" . SHORTDATEFORMAT_SQL . ' ' . TIMEFORMAT_SQL . "') AS reported,
									DATE_FORMAT(commentreports.resolved, '" . SHORTDATEFORMAT_SQL . ' ' . TIMEFORMAT_SQL . "') AS resolved,
									commentreports.resolvedby,
									commentreports.locked,
									commentreports.lockedby,
									commentreports.upheld,
									commentreports.firstname,
									commentreports.lastname,
									commentreports.email,
									users.firstname AS u_firstname,
									users.lastname AS u_lastname
							FROM	commentreports,
									users
                            WHERE	commentreports.report_id = '" . $this->db->escape($report_id) . "'
							AND		commentreports.user_id = users.user_id
							");

            if ($q->rows() > 0) {
                $this->report_id = $report_id;
                $this->comment_id = $q->field(0, 'comment_id');
                $this->body = $q->field(0, 'body');
                $this->reported = $q->field(0, 'reported');
                $this->resolved = $q->field(0, 'resolved');
                $this->resolvedby = $q->field(0, 'resolvedby');
                $this->locked = $q->field(0, 'locked');
                $this->lockedby = $q->field(0, 'lockedby');
                $this->upheld = $q->field(0, 'upheld');

                if ($q->field(0, 'user_id') == 0) {
                    // The report was made by a non-logged-in user.
                    $this->firstname = $q->field(0, 'firstname');
                    $this->lastname = $q->field(0, 'lastname');
                    $this->email = $q->field(0, 'email');
                } else {
                    // The report was made by a logged-in user.
                    $this->firstname = $q->field(0, 'u_firstname');
                    $this->lastname = $q->field(0, 'u_lastname');
                    $this->user_id = $q->field(0, 'user_id');
                }
            } else {
                $q = $this->db->query("SELECT commentreports.comment_id,
									commentreports.user_id,
									commentreports.body,
									DATE_FORMAT(commentreports.reported, '" . SHORTDATEFORMAT_SQL . ' ' . TIMEFORMAT_SQL . "') AS reported,
									DATE_FORMAT(commentreports.resolved, '" . SHORTDATEFORMAT_SQL . ' ' . TIMEFORMAT_SQL . "') AS resolved,
									commentreports.resolvedby,
									commentreports.locked,
									commentreports.lockedby,
									commentreports.upheld,
									commentreports.firstname,
									commentreports.lastname,
									commentreports.email
							FROM	commentreports
                            WHERE	commentreports.report_id = '" . $this->db->escape($report_id) . "'");

                if ($q->rows() > 0) {
                    $this->report_id = $report_id;
                    $this->comment_id = $q->field(0, 'comment_id');
                    $this->body = $q->field(0, 'body');
                    $this->reported = $q->field(0, 'reported');
                    $this->resolved = $q->field(0, 'resolved');
                    $this->resolvedby = $q->field(0, 'resolvedby');
                    $this->locked = $q->field(0, 'locked');
                    $this->lockedby = $q->field(0, 'lockedby');
                    $this->upheld = $q->field(0, 'upheld');
                    $this->firstname = $q->field(0, 'firstname');
                    $this->lastname = $q->field(0, 'lastname');
                    $this->email = $q->field(0, 'email');
                }
            }
        }
    }

    /**
     *
     */
    public function report_id() {
        return $this->report_id;
    }

    /**
     *
     */
    public function comment_id() {
        return $this->comment_id;
    }

    /**
     *
     */
    public function user_id() {
        return $this->user_id;
    }

    /**
     *
     */
    public function user_name() {
        return $this->firstname . ' ' . $this->lastname;
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
    public function email() {
        return $this->email;
    }

    /**
     *
     */
    public function body() {
        return $this->body;
    }

    /**
     *
     */
    public function reported() {
        return $this->reported;
    }

    /**
     *
     */
    public function resolved() {
        return $this->resolved;
    }

    /**
     *
     */
    public function resolvedby() {
        return $this->resolvedby;
    }

    /**
     *
     */
    public function locked() {
        return $this->locked;
    }

    /**
     *
     */
    public function lockedby() {
        return $this->lockedby;
    }

    /**
     *
     */
    public function upheld() {
        return $this->upheld;
    }

    /**
     *
     */
    public function display() {

        $data = [];

        if (is_numeric($this->report_id)) {
            $data = [
                'report_id' => $this->report_id(),
                'comment_id' => $this->comment_id(),
                'user_id' => $this->user_id(),
                'user_name' => $this->user_name(),
                'body' => $this->body(),
                'reported' => $this->reported(),
                'resolved' => $this->resolved(),
                'resolvedby' => $this->resolvedby(),
                'locked' => $this->locked(),
                'lockedby' => $this->lockedby(),
                'upheld' => $this->upheld()
            ];
        }

        $this->render($data);
    }

    /**
     *
     */
    public function render($data) {
        global $PAGE;

        $PAGE->display_commentreport($data);

    }

    /**
     *
     */
    public function lock() {
        // Called when an admin user goes to examine a report, so that
        // only one person can edit at once.

        global $THEUSER, $PAGE;

        if ($THEUSER->is_able_to('deletecomment')) {
            $time = gmdate("Y-m-d H:i:s");

            $q = $this->db->query("UPDATE commentreports
							SET		locked = '$time',
									lockedby = '" . $THEUSER->user_id() . "'
							WHERE	report_id = '" . $this->report_id . "'
							");

            if ($q->success()) {
                $this->locked = $time;
                $this->lockedby = $THEUSER->user_id();
                return TRUE;
            } else {
                $PAGE->error_message("Sorry, we were unable to lock this report.");
                return FALSE;
            }
        } else {
            $PAGE->error_message("You are not authorised to delete comments.");
            return FALSE;
        }
    }

    /**
     *
     */
    public function resolve($upheld, $COMMENT) {
        // Resolve a report.
        // $upheld is true or false.
        // $COMMENT is an existing COMMENT object - we need this so
        // that we can set its modflagged to off and/or delete it.
        global $THEUSER, $PAGE;

        $time = gmdate("Y-m-d H:i:s");

        if ($THEUSER->is_able_to('deletecomment')) {
            // User is allowed to do this.

            if (!$this->resolved) {
                // Only if this report hasn't been previously resolved.

                if ($upheld) {

                    $success = $COMMENT->delete();

                    if (!$success) {
                        // Abort!
                        return FALSE;
                    }

                    $upheldsql = '1';

                } else {
                    $upheldsql = '0';

                    // Report has been removed, so un-modflag this comment.
                    $COMMENT->set_modflag('off');
                }

                $q = $this->db->query("UPDATE commentreports
								SET 	resolved = '$time',
										resolvedby = '" . $this->db->escape($THEUSER->user_id()) . "',
										locked = NULL,
										lockedby = NULL,
										upheld = '$upheldsql'
								WHERE 	report_id = '" . $this->db->escape($this->report_id) . "'
								");

                if ($q->success()) {

                    $this->resolved = $time;
                    $this->resolvedby = $THEUSER->user_id();
                    $this->locked = NULL;
                    $this->lockedby = NULL;
                    $this->upheld = $upheld;

                    return TRUE;
                } else {
                    $PAGE->error_message("Sorry, we couldn't resolve this report.");
                    return FALSE;
                }
            } else {
                $PAGE->error_message("This report has already been resolved (on " . $this->resolved . ")");
                return FALSE;
            }

        } else {
            $PAGE->error_message("You are not authorised to resolve reports.");
            return FALSE;
        }
    }

}
