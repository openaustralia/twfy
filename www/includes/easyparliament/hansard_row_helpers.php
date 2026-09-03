<?php

/**
 * @file
 * Two small HTML-fragment builders shared by both hansard_gid.php's legacy
 * stripe rendering and HansardSpeechView.php's Plates view-model layer - moved
 * here (out of hansard_gid.php, where they used to live as plain functions)
 * so HansardSpeechView.php doesn't depend on whichever template happened to
 * include it first. hansard_gid_mobile.php still declares its own separate
 * copies of both names (different signatures/behaviour, not unified here -
 * see openaustralia/openaustralia#943, that template's slated for removal
 * rather than reuse). Ben Fairless's review on #227.
 */

// function_exists guards: tests/bootstrap.php stubs both with a plain,
// distinct-output version (not worth standing up a database/$THEUSER/
// $hansardmajors for the unit tests that exercise forSpeech()/forProcedural()'s
// wiring) - the guard here lets that stub win when the test suite's own
// bootstrap loads first, same pattern bootstrap.php already uses for
// twfy_debug() etc.
if (!function_exists('context_link')) {

    /**
     * Returns the "See this X in context" link's HTML, or '' outside a
     * single-debate page. Used to echo directly inline in the old
     * stripe-based rendering, and to build a HansardSpeechView/
     * HansardProceduralView field in the Plates-rendered path - kept as one
     * function, not duplicated, since both paths want exactly the same link.
     */
    function context_link($row) {
        global $this_page;

        if ($this_page != 'debate') {
            return '';
        }

        $thing = $row['htype'] == '12' ? 'speech' : 'item';

        // Plain concatenation, not ob_start()/ob_get_clean(): this runs once per
        // row on a transcript page, which can run to dozens of rows - avoidable
        // buffering overhead for a string this simple. Copilot finding on #227.
        return '<p><small><strong><a href="' . $row['listurl'] . '" class="permalink"'
            . ' title="See this ' . $thing . ' within the entire debate">See this ' . $thing . ' in'
            . ' context</a></strong></small></p>';
    }

}

if (!function_exists('generate_commentteaser')) {

    /**
     * $totalcomments, $comment, $commenturl
     */
    function generate_commentteaser($row, $major) {
        // Returns HTML for the one fragment of comment and link for the sidebar.
        // $totalcomments is the number of comments this item has on it.
        // $comment is an array like:
        /* $comment = array (
            'comment_id' => 23,
            'user_id'   => 34,
            'body'      => 'Blah blah...',
            'posted'    => '2004-02-24 23:45:30',
            'username'  => 'phil'
            )
        */
        // $url is the URL of the item's page, which contains comments.

        global $this_page, $THEUSER, $hansardmajors;

        $html = '';

        if ($hansardmajors[$major]['type'] == 'debate' && $hansardmajors[$major]['page_all'] == $this_page) {

            if ($row['totalcomments'] > 0) {
                $comment = $row['comment'];

                // If the comment is longer than the speech body, we want to trim it
                // to be the same length so they fit next to each other.
                // But the comment typeface is smaller, so we scale things slightly too...
                $targetsize = round(strlen($row['body']) * 0.6);

                if ($targetsize > strlen($comment['body'])) {
                    // This comment will fit in its entirety.
                    $commentbody = $comment['body'];

                    if ($row['totalcomments'] > 1) {
                        $morecount = $row['totalcomments'] - 1;
                        $plural = $morecount == 1 ? 'comment' : 'comments';
                        $linktext = "Read $morecount more $plural";
                    }

                } else {
                    // This comment needs trimming.
                    $commentbody = htmlentities(trim_characters($comment['body'], 0, $targetsize));
                    if ($row['totalcomments'] > 1) {
                        $morecount = $row['totalcomments'] - 1;
                        $plural = $morecount == 1 ? 'comment' : 'comments';
                        $linktext = "Continue reading (and $morecount more $plural)";
                    } else {
                        $linktext = 'Continue reading';
                    }
                }

                $html = '<em>' . htmlentities($comment['username']) . '</em>: ' . prepare_comment_for_display($commentbody);

                if (isset($linktext)) {
                    $html .= ' <a href="' . $row['commentsurl'] . '#c' . $comment['comment_id'] . '" title="See any comments posted about this">' . $linktext . '</a>';
                }

                $html .= '<br><br>';
            }

            // 'Add a comment' link.
            if (!$THEUSER->isloggedin()) {
                $URL = new URL('userprompt');
                $URL->insert(['ret' => $row['commentsurl']]);
                $commentsurl = $URL->generate();
            } else {
                $commentsurl = $row['commentsurl'];
            }

            // No wrapping <p> when there's nothing inside it - speech.php's footer
            // row puts this next to "Hansard source"/"Link to this" on one line,
            // and an empty <p> (block-level even with no content) breaks that.
            if ($html !== '') {
                $html = "\t\t\t\t" . '<p class="comment-teaser">' . $html . "</p>\n";
            }
        }

        return $html;
    }

}
