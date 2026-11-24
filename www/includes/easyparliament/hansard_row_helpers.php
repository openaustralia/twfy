<?php

/**
 * @file
 * A small HTML-fragment builder shared by both hansard_gid.php's legacy
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
