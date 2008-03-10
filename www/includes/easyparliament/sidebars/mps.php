<?php

$URL = new URL($this_page);
$URL->insert(array('f'=>'csv'));
$csvurl = $URL->generate();

$this->block_start(array('title'=>"This data as a spreadsheet",
	'url' => $csvurl,
	'body'=>''));
?>
<p>
	Click <?php echo '<a href="'.$csvurl.'">here</a>'; ?> to download a CSV (Comma Separated Values) file that you can load into Excel.
</p>
<?php
$this->block_end();
?>
