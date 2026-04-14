<?php

/**
 * A class for doing things with single comments.
 *
 * To access stuff about an existing comment you can do something like:
 * $COMMENT = new COMMENT(37);
 * $COMMENT->display();
 * Where '37' is the comment_id.
 *
 * To create a new comment you should get a $data array prepared of
 * the key/value pairs needed to create a new comment and do:
 * $COMMENT = new COMMENT;
 * $COMMENT->create ($data);
 *
 * You can delete a comment by doing $COMMENT->delete() (it isn't actually
 * deleted from the database, just set to invisible.
 *
 * You can also do $COMMENT->set_modflag() which happens when a user
 * posts a report about a comment. The flag is unset when/if the report is
 * rejected.
 */
class COMMENT {

    private $db = NULL;

    public $comment_id = '';
    public $user_id = '';
    public $epobject_id = '';
    public $body = '';
    public $posted = '';
    public $visible = FALSE;
    /**
     * Is a datetime when set.
     */
    public $modflagged = NULL;
    public $firstname = '';    /**
                                * Of the person who posted it.
                                */
    public $lastname = '';
    public $url = '';

    // So that after trying to init a comment, we can test for.
    /**
     * If it exists in the DB.
     */
    public $exists = FALSE;

    /**
     *
     */
    public function __construct($comment_id = '') {

        $this->db = new ParlDB();

        // Set in init.php.
        if (ALLOWCOMMENTS == TRUE) {
            $this->comments_enabled = TRUE;
        } else {
            $this->comments_enabled = FALSE;
        }

        if (is_numeric($comment_id)) {
            // We're getting the data for an existing comment from the DB.

            $q = $this->db->query("SELECT user_id,
									epobject_id,
									body,
									posted,
									visible,
									modflagged
							FROM	comments
							WHERE 	comment_id='" . addslashes($comment_id) . "'
							");

            if ($q->rows() > 0) {

                $this->comment_id = $comment_id;
                $this->user_id = $q->field(0, 'user_id');
                $this->epobject_id = $q->field(0, 'epobject_id');
                $this->body = $q->field(0, 'body');
                $this->posted = $q->field(0, 'posted');
                $this->visible = $q->field(0, 'visible');
                $this->modflagged = $q->field(0, 'modflagged');

                // Sets the URL and username for this comment. Duh.
                $this->_set_url();
                $this->_set_username();

                $this->exists = TRUE;
            } else {
                $this->exists = FALSE;
            }
        }
    }

    /**
     * Use these for accessing the object's variables externally.
     */
    public function comment_id() {
        return $this->comment_id;
    }


    /**
     *
     */
    public function exists() {
        return $this->exists;
    }

    /**
     *
     */
    public function comments_enabled() {
        return $this->comments_enabled;
    }


    /**
     *
     */
    public function display($format = 'html', $template = 'comments') {

        $data['comments'][0] = [
            'comment_id' => $this->comment_id,
            'user_id' => $this->user_id,
            'epobject_id' => $this->epobject_id,
            'body' => $this->body,
            'posted' => $this->posted,
            'modflagged' => $this->modflagged,
            'url' => $this->url,
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'visible' => $this->visible,
        ];

        // Use the same renderer as the COMMENTLIST class.
        $COMMENTLIST = new COMMENTLIST();
        $COMMENTLIST->render($data, $format, $template);

    }

    /**
     *
     */
    public function _set_url() {
        global $hansardmajors;
        // Creates and sets the URL for the comment.

        if ($this->url == '') {

            $q = $this->db->query("SELECT major,
									gid
							FROM	hansard
							WHERE	epobject_id = '" . addslashes($this->epobject_id) . "'
							");

            if ($q->rows() > 0) {
                // If you change stuff here, you might have to change it in
                // $COMMENTLIST->_get_comment_data() too...

                // In includes/utility.php.
                $gid = fix_gid_from_db($q->field(0, 'gid'));

                $major = $q->field(0, 'major');
                $page = $hansardmajors[$major]['page'];
                $gidvar = $hansardmajors[$major]['gidvar'];

                $URL = new URL($page);
                $URL->insert([$gidvar => $gid]);
                $this->url = $URL->generate() . '#c' . $this->comment_id;
            }
        }
    }

    /**
     *
     */
    public function _set_username() {
        // Gets and sets the user's name who posted the comment.

        if ($this->firstname == '' && $this->lastname == '') {
            $q = $this->db->query("SELECT firstname,
									lastname
							FROM	users
							WHERE	user_id = '" . addslashes($this->user_id) . "'
							");

            if ($q->rows() > 0) {
                $this->firstname = $q->field(0, 'firstname');
                $this->lastname = $q->field(0, 'lastname');
            }
        }
    }

}
