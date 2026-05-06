<?php

/**
 * @file
 * This code based on stuff from http://wordpress.org/
 */

include_once __DIR__ . "/../../includes/easyparliament/init.php";
include_once __DIR__ . "/../../includes/request.php";

$this_page = 'trackback';

// The gid is the gid of the thing being trackedback to.
// eg, '3424'.
$epobject_id = get_http_var('e');

$url = get_http_var('url');
$blog_name = get_http_var('blog_name');
$title = get_http_var('title');
$excerpt = get_http_var('excerpt');

if ($title == '' && $url == '' && $blog_name == '') {
    // If it doesn't look like a trackback at all...
    // We could/should redirect to the URL of this particular item.

    // Word Press does this:
    // header('Location: ' . get_permalink($gid));

    // But for now we're just getting the hell outta here:
    exit;
}


if ((strlen('' . $epobject_id)) && (empty($HTTP_GET_VARS['__mode'])) && (strlen('' . $url))) {

    header('Content-Type: text/xml');

    $trackbackdata = [
        'epobject_id' => $epobject_id,
        'url' => $url,
        'blog_name' => $blog_name,
        'title' => $title,
        'excerpt' => $excerpt,
        'source_ip' => ip_address()
    ];

    $TRACKBACK = new TRACKBACK();

    $TRACKBACK->add($trackbackdata);

}
