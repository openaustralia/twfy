<?php

/**
 * @file
 */

include_once __DIR__ . "/../../includes/easyparliament/init.php";
include_once __DIR__ . "/../../includes/easyparliament/glossary.php";

$this_page = "debate";

// For displaying a SINGLE speech from a debate.


if (get_http_var('id') != '') {
    // We have the id of the gid of a Hansard item to display, so show it.

    $args = [
        'gid' => get_http_var('id'),
        'glossarise' => 1,
        'sort' => 'regexp_replace',
    ];

    $DEBATELIST = new DEBATELIST();
    $GLOSSARY = new GLOSSARY($args);

    $result = $DEBATELIST->display('gid', $args);
    // If it is a redirect, change URL.
    if (is_string($result)) {
        $URL = new URL('debate');
        $URL->insert(['id' => $result]);
        header('Location: //' . DOMAIN . $URL->generate('none'), true, 301);
        exit;
    }


} else {
    $PAGE->error_message("We need a gid");
}

$PAGE->page_end();
