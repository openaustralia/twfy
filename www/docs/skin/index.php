<?php

/**
 * @file
 */

$this_page = "skin";

include_once "../../includes/easyparliament/init.php";

$PAGE->page_start();

$PAGE->stripe_start();

$URL = new URL($this_page);

?>

<ul>

    <?php $URL->insert(['newskin' => 'default']); ?>
    <li><a href="<?php echo $URL->generate(); ?>">Default skin.</a></li>

    <?php $URL->insert(['newskin' => 'none']); ?>
    <li><a href="<?php echo $URL->generate(); ?>">No stylesheets.</a></li>

</ul>


<?php

$PAGE->stripe_end();

$PAGE->page_end();
