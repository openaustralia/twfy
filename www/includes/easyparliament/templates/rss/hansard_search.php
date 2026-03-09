<?php

/**
 * @file
 */

global $SEARCHENGINE;
header("Content-Type: text/xml; charset=iso-8859-1");
print '<?xml version="1.0" encoding="iso-8859-1"?>'; ?>

<rss version="2.0" xmlns:openSearch="http://a9.com/-/spec/opensearchrss/1.0/">
<channel>
<title>Search: <?php echo $SEARCHENGINE->query_description_short() ?> (OpenAustralia.org)</title>
<link>http://www.openaustralia.org<?php echo htmlentities(str_replace('rss/', '', $_SERVER['REQUEST_URI'])) ?></link>
<description>Search results for <?php echo $SEARCHENGINE->query_description_short() ?> at OpenAustralia.org</description>
<language>en-gb</language>
<copyright>Parliamentary Copyright.</copyright>
<?php if (isset($data['info']['total_results'])) { ?>
<openSearch:totalResults><?php echo $data['info']['total_results'] ?></openSearch:totalResults>
<?php }
if (isset($data['info']['first_result'])) { ?>
<openSearch:startIndex><?php echo $data['info']['first_result'] ?></openSearch:startIndex>
<?php }
if (isset($data['info']['results_per_page'])) { ?>
<openSearch:itemsPerPage><?php echo $data['info']['results_per_page'] ?></openSearch:itemsPerPage>
<?php }

global $this_page;
twfy_debug("TEMPLATE", "rss/hansard_search.php");

if (isset($data['rows']) && count($data['rows']) > 0) {
    for ($i = 0; $i < count($data['rows']); $i++) {
        $row = $data['rows'][$i];
        ?>
<item>
<title><?php
if (isset($row['parent']) && count($row['parent']) > 0) {
    echo strip_tags($row['parent']['body']);
}
echo (' (' . format_date($row['hdate'], SHORTDATEFORMAT) . ')');
?></title>
<link>http://www.openaustralia.org<?php echo $row['listurl'] ?></link>
<guid>http://www.openaustralia.org<?php echo $row['listurl'] ?></guid>
<description><?php
if (isset($row['speaker']) && count($row['speaker'])) {
    $sp = $row['speaker'];
    $name = ucfirst(member_full_name($sp['house'], $sp['title'], $sp['first_name'], $sp['last_name'], $sp['constituency']));
    echo entities_to_numbers($name) . ': ';
}
echo htmlspecialchars(str_replace(['&#8212;', '<span class="hi">', '</span>'], ['-', '<b>', '</b>'], $row['body'])) . "</description>\n</item>\n";
    }
}
?>
</channel>
</rss>
