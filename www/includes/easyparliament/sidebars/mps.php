<?php

/**
 * @file
 */

$URL = new URL($this_page);
$URL->insert(['f' => 'csv']);
$csvurl = $URL->generate();

$this->block_start([
    'title' => "This data as a spreadsheet",
    'url' => $csvurl,
    'body' => ''
]);
?>
<p>
    <a href="<?php echo $csvurl ?>">Download a CSV</a> (Comma Separated Values) file that you can load into your
    favourite spreadsheet program or data-mashing software.
</p>
<?php
$this->block_end();
