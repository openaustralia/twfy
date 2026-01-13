<?php

/**
 * @file
 */

include_once "../../includes/easyparliament/init.php";
$this_page = "admin_statistics";

$db = new ParlDB();

$PAGE->page_start();

$PAGE->stripe_start();


?>

<h4>Hansard data in database</h4>

<?php
$DEBATELIST = new DEBATELIST();
$debate_items = $DEBATELIST->total_items();

$WRANSLIST = new WRANSLIST();
$wrans_items = $WRANSLIST->total_items();

$debate_speeches = $DEBATELIST->total_speeches();

$wrans_questions = $WRANSLIST->total_questions();

$q = $db->query("SELECT min(hdate) as mindate, max(hdate) as maxdate from hansard");
$datefrom = format_date($q->field(0, 'mindate'), SHORTDATEFORMAT);
$dateto = format_date($q->field(0, 'maxdate'), SHORTDATEFORMAT);

$q = $db->query("SELECT count(distinct hdate) as count from hansard");
$uniquedates = $q->field(0, 'count');
?>


<p><b><?php echo $datefrom?></b> to <b><?php echo $dateto?></b>. Parliament was sitting for
<b><?php echo $uniquedates?></b> of those days.

<p>There are <b><?php echo number_format($debate_speeches)?></b> debate speeches (<?php echo number_format($debate_items)?> items including headers).
<br>There are <b><?php echo number_format($wrans_questions)?></b> written questions (<?php echo number_format($wrans_items)?> items including headers and answers).

<!-- Debate items / in-session day: <?php echo round($debate_items / $uniquedates, 0)?>
Wrans items / in-session day: <?php echo round($wrans_items / $uniquedates, 0)?> -->

<p>Per sitting day, MPs are producing <b><?php echo round($debate_speeches / $uniquedates, 0)?></b> speeches, and <b><?php echo round($wrans_questions / $uniquedates, 0)?></b> written answers.
</p>

<?php

$menu = $PAGE->admin_menu();

$PAGE->stripe_end([
    [
        'type'        => 'html',
        'content'    => $menu
    ]
]);

$PAGE->page_end();
