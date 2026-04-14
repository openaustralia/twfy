<?php

/*     The class for displaying one or more comments.
(There's also a function for adding a new comment to the DB because I wasn't
sure where else to put it!).

This works similarly to the HANSARDLIST class.

To display all the comments for an epobject you'll do:

$args = array ('epobject_id' => $epobject_id);
$COMMENTLIST = new COMMENTLIST;
$COMMENTLIST->display ('ep', $args);

This will call the _get_data_by_ep() function which passes variables to the
_get_comment_data() function. This gets the comments from the DB and returns
an array of comments.

The render() function is then called, which includes a template and
goes through the array, displaying the comments. See the HTML comments.php
template for the format.
NOTE: You'll need to pass the 'body' of the comment through filter_user_input()
and linkify() first.

You could also just call the $COMMENTLIST->render() array with an array
of comment data and display directly (used for previewing user input).

 */

include_once INCLUDESPATH . 'dbtypes.php';

/**
 *
 */
class COMMENTLIST {

    private $db = NULL;

    /**
     *
     */
    public function __construct() {
        global $this_page;

        $this->db = new ParlDB();

        // We use this to create permalinks to comments. For the moment we're
        // assuming they're on the same page we're currently looking at:
        // debate, wran, etc.
        $this->page = $this_page;

    }

    /**
     *
     */
    public function display($view, $args = [], $format = 'html') {
        // $view is what we're viewing by:
        // 'ep' is all the comments attached to an epobject.
        // 'user' is all the comments written by a user.
        // 'recent' is the most recent comments.

        // $args is an associative array of stuff like
        // 'epobject_id' => '37'
        // Where 'epobject_id' is an epobject_id.
        // Or 'gid' is a hansard item gid.

        // Replace a hansard object gid with an epobject_id.
        // $args = $this->_fix_gid($args);

        // $format is the format the data should be rendered in.

        if ($view == 'ep' || $view == 'user' || $view == 'recent' || $view == 'search') {
            // What function do we call for this view?
            $function = '_get_data_by_' . $view;
            // Get all the dta that's to be rendered.
            $data = $this->$function($args);

        } else {
            global $PAGE;
            // Don't have a valid $view;.
            $PAGE->error_message("You haven't specified a view type.");
            return FALSE;
        }

        if ($view == 'user') {
            $template = 'comments_user';
        } elseif ($view == 'recent') {
            $template = 'comments_recent';
        } elseif ($view == 'search') {
            $template = 'comments_search';
        } else {
            $template = 'comments';
        }

        $this->render($data, $format, $template);

        return TRUE;
    }

    /**
     *
     */
    public function render($data, $format = 'html', $template = 'comments') {
        include INCLUDESPATH . "easyparliament/templates/$format/$template.php";
    }

    /**
     *
     */
    public function _comment_url($urldata) {
        global $hansardmajors;

        // Pass it the major and gid of the comment's epobject and the comment_id.
        // And optionally the user's id, for highlighting the comments on the destination page.
        // It returns the URL for the comment.

        $major = $urldata['major'];
        $gid = $urldata['gid'];
        $comment_id = $urldata['comment_id'];
        $user_id = $urldata['user_id'] ?? FALSE;

        // If you change stuff here, you might have to change it in
        // $COMMENT->_set_url() too...

        // We'll generate permalinks for each comment.
        // Assuming every comment is from the same major...
        $page = $hansardmajors[$major]['page'];
        $gidvar = $hansardmajors[$major]['gidvar'];

        $URL = new URL($page);

        // In includes/utility.php.
        $gid = fix_gid_from_db($gid);
        $URL->insert([$gidvar => $gid]);
        if ($user_id) {
            $URL->insert(['u' => $user_id]);
        }
        $url = $URL->generate() . '#c' . $comment_id;

        return $url;
    }

}
