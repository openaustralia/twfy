<?php
// We're within $DEBATESLIST->render().

/*
    $data = array (
        'info' => '',
        'data' => array (
            array (
                'contentcount' 	=> 128,
                'body'			=> 'My big bill',
                'hdate'			=> '2004-03-24',
                'list_url'		=> '/debates/?id=2004-03-24.342.234',
                'totalcomments'	=> 2,
                'parent'	=> array (
                    'body'		=> 'My new clause 23'
                )
            ),
            etc.
        )
    );

    The 'parent' element is optional.

*/

twfy_debug("TEMPLATE", "hansard_biggest_debates.php");
if (array_key_exists('data', $data) && is_array($data['data'])) {
    ?>
    <dl class="big-debates oa-big-debates">
        <?php

        $count = 0;

        foreach ($data['data'] as $debate) {

            $count++;

            $extrainfo = array();

            $plural = $debate['contentcount'] == 1 ? 'speech' : 'speeches';
            $extrainfo[] = $debate['contentcount'] . ' ' . $plural;

            if ($debate['totalcomments'] > 0) {
                $plural = $debate['totalcomments'] == 1 ? 'comment' : 'comments';
                $extrainfo[] = $debate['totalcomments'] . ' ' . $plural;
            }

            $debate_title = htmlentities($debate['body']);
            $debate_date = htmlentities(format_date($debate['hdate'], LONGERDATEFORMAT));
            $extra_info = htmlentities(implode(', ', $extrainfo));
            $debate_label = htmlentities('View debate: ' . $debate['body']);

            $debateline = '<strong class="oa-big-debates-title"><a class="oa-big-debates-link" href="' . $debate['list_url'] . '" aria-label="' . $debate_label . '">' . $debate_title . '</a></strong> <small class="oa-big-debates-meta">' . $extra_info . '</small>';

            if (isset($debate['parent'])) {
                $parent_title = htmlentities($debate['parent']['body']);
                $parent_label = htmlentities('View debate section: ' . $debate['parent']['body']);
                $firstline = '<strong class="oa-big-debates-parent"><a class="oa-big-debates-link" href="' . $debate['list_url'] . '" aria-label="' . $parent_label . '">' . $parent_title . '</a></strong>';
                $secondline = '<span class="oa-big-debates-child">' . $debateline . "</span><br>\n\t\t\t\t";
            } else {
                $firstline = $debateline;
                $secondline = '';
            }
            $secondline .= '<span class="oa-big-debates-date">' . $debate_date . '</span>';

            ?>
            <dt class="oa-big-debates-dt"><a id="d<?php echo $count; ?>" name="d<?php echo $count; ?>"></a><?php echo $firstline; ?></dt>
            <dd class="oa-big-debates-dd"><?php echo $secondline; ?></dd>
            <?php
        }
        ?>
    </dl>
<?php }
