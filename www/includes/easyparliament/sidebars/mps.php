<?php

$URL = new URL($this_page);
$URL->insert(array('f'=>'csv'));
$csvurl = $URL->generate();

$this->block_start(array('title'=>"This data as a spreadsheet",
	'url' => $csvurl,
	'body'=>''));
?>
<p>
	<a href="<?= $csvurl ?>">Download a CSV</a> (Comma Separated Values) file that you can load into your favourite spreadsheet program or data-mashing software.
</p>
<?php
$this->block_end();
?>
