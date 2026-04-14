<?php

/**
 * @file
 * This file will be included by data.php.
 */

// The path of the file should be set as METADATAPATH in config.php.

// What are session_vars ?
// When generating a URL to a page using the URL class (in url.php), any
// GET variables for the page whose keys are listed in its session_vars below
// will automatically be put in the URL.

// For example, in this metadata we might have:
// 'search' => array (
// 'url' => 'search/',
// 'sidebar' => 'search',
// 'session_vars' => array ('s')
// ),.

// If we are at the URL www.domain.org/search/?s=blair&page=2
// and we used the URL class to generate a link to the search page like this:
// $URL = new URL('search');
// $newurl = $URL->generate();

// Then $newurl would be: /search/?s=blair
//
// sidebar:
// If you have a 'sidebar' element for a page then that page will have its content
// set to a restricted width and a sidebar will be inserted. The contents of this
// will be include()d from a file in template/sidebars/ of the name of the 'sidebar'
// value ('search.php' in the example above).

/**
 * Items a page might have:
 *
 * menu        An array of 'text' and 'title' which are used if the page
 * appears in the site menu.
 * title        Used for the <title> and the page's heading on the page.
 * heading        If present *this* is used for the page's heading on the page, in
 * in place of the title.
 * url            The URL from the site webroot for this page.
 * parent        What page is this page's parent (see below).
 * session_vars        If present, whenever a URL is generated to this page using the
 * URL class, any POST/GET variables with matching names are
 * automatically appended to the url.
 * track (deprecated)         Do we want to include the Extreme Tracker javascript on this page?
 * rss            Does the content of this page (or some of it) have an RSS version?
 * If so, 'rss' should be set to '/a/path/to/the/feed.rdf'.
 *
 *
 * PARENTS
 * The site's menu has a top menu and a bottom, sub-menu. What is displayed in the
 * sub-menu depends on which page is selected in the top menu. This is worked out
 * from the bottom up, by looking at pages' parents. Here's an example top and bottom
 * menu, with the capitalised items hilited:
 *
 * Home    HANSARD        Glossary    Help
 *
 * DEBATES        Written Answers
 *
 * If we were viewing a particular debate, we would be on the 'debate' page. The parent
 * of this is 'debatesfront', which is the DEBATES link in the bottom menu - hence its
 * hilite. The parent of 'debatesfront' is 'hansard', hence its hilite in the top menu.
 *
 * This may, of course, make no sense at all...
 *
 * If a page has no parent it is either in the top menu or no menu items should be hilited.
 * The actual contents of each menu is determined in $PAGE->menu().
 *
 *
 */
class Metadata {

    /**
     *
     */
    public function set_metadata($args) {

        if (isset($args["section"])) {
            $type = "section";
            $item = $args["section"];
        } else {
            $type = "page";
            $item = $args["page"];
        }

        $key = $args["key"];
        $value = $args["value"];

        twfy_debug("DATA", "Setting: " . $type . "[" . $item . "][" . $key . "] = '" . print_r($value, TRUE) . "'");

        $this->$type[$item][$key] = $value;
    }

    /**
     *
     */
    public function get_metadata($args, $type) {

        if (is_array($args)) {
            $item = $args[$type];
            $key = $args['key'];
        } else {
            $var = "this_" . $type;
            // $this_page or $this_section.
            $item = $$var;
            $key = $args;
        }

        twfy_debug("DATA", "$type: $item, $key");

        // If the item requested exists, return it.
        if (isset($this->$type[$item][$key])) {
            return $this->$type[$item][$key];
        } elseif (isset($this->$type['default'][$key])) {
            return $this->$type['default'][$key];
        }

        // We got nothin'.
        return FALSE;
    }

}
