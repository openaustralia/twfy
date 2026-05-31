<?php

/*
Used on the 'All MPs' page to produce the list of MPs.

$data = array (
    'info' => array (
        'order' => 'first_name'
    ),
    'data' => array (
        'first_name'	=> 'Fred',
        'last_name'		=> 'Bloggs,
        'person_id'		=> 23,
        'constituency'	=> 'Here',
        'party'			=> 'Them'
    )
);
*/

global $this_page, $THEUSER;

twfy_debug("TEMPLATE", "people_mps.php");

$MPURL = new URL('yourmp');
$MP_RECENT_URL = new URL('yourmp_recent');

// Hack hack
if ($THEUSER->constituency_is_set()) {
    // (We don't allow the user to search for a postcode if they
    // already have one set in their prefs.)

    $MEMBER = new MEMBER(array('constituency' => $THEUSER->constituency()));
    if ($MEMBER->valid) {
        $pc_form = false;
        $CHANGEURL = new URL('userchangepc');
        $mpname = $MEMBER->first_name() . ' ' . $MEMBER->last_name();
        $mpname_safe = htmlentities($mpname);
        $former = "";
        $left_house = $MEMBER->left_house();
        if ($left_house[1]['date'] != '9999-12-31') {
            $former = 'former';
        }
        ?>
        <div class="oa-people-intro">
        <p class="oa-people-intro-primary">Find out more about <a
            href="<?php echo htmlentities($MPURL->generate()); ?>"><strong><?php echo $mpname_safe; ?>, your <?php echo htmlentities($former) ?>
                    Representative</strong></a>, including their <a href="<?php echo $MP_RECENT_URL->generate() ?>">most recent
                speeches</a>.</p>
        <p class="oa-people-intro-secondary">If <?php echo $mpname_safe; ?> is not your Representative, <a
            href="<?php echo htmlentities($CHANGEURL->generate()); ?>">provide a new postcode</a>.</p>
        </div>
        <?php
    }
} else {
    ?>
        <p class="oa-people-intro-primary">Find out who <a href="<?php echo htmlentities($MPURL->generate()) ?>">your
            Representative</a> is. All you need is a postcode.</p>
    <?php
}

?>
<?php

$order = $data['info']['order'];

$URL = new URL($this_page);

if ($order == 'first_name') {
    $th_name = 'First';
} else {
    $URL->insert(array('o' => 'f'));
    $th_name = '<a href="' . $URL->generate() . '">First</a>';
}
$th_name .= ' &amp; ';
if ($order == 'last_name') {
    $th_name .= 'Last';
} else {
    $URL->insert(array('o' => 'l'));
    $th_name .= '<a href="' . $URL->generate() . '">Last</a>';
}
$th_name .= ' name';
$URL->insert(array('o' => 'p'));
$th_party = '<a href="' . $URL->generate() . '">Party</a>';
$URL->insert(array('o' => 'c'));
$th_constituency = '<a href="' . $URL->generate() . '">Constituency</a>';

if ($order == 'party') {
    $th_party = 'Party';
} elseif ($order == 'constituency') {
    $th_constituency = 'Constituency';
}

?>
<table border="0" cellpadding="4" cellspacing="0" width="90%" class="people oa-people-table">
    <caption class="oa-sr-only">List of representatives, including party, constituency, and positions</caption>
    <thead>
        <tr>
            <th scope="col">Photo</th>
            <th scope="col"><?php echo $th_name; ?></th>
            <th scope="col"><?php echo $th_party; ?></th>
            <th scope="col"><?php echo $th_constituency; ?></th>
            <th scope="col">Positions</th>
            <?php if ($order == 'expenses') { ?>
                <th scope="col">2004 Expenses Grand Total</th>
            <?php } elseif ($order == 'debates') { ?>
                <th scope="col">Debates spoken in the last year</th>
            <?php } elseif ($order == 'safety') { ?>
                <th scope="col">Swing to lose seat (%)</th>
            <?php }
            ?>
        </tr>
    </thead>
    <tbody>
        <?php

        $MPURL = new URL(str_replace('s', '', $this_page));
        $style = '2';

        $opik = array();

        foreach ($data['data'] as $pid => $mp) {

            // Lembit Opik is special
            if ($mp['last_name'] == '&Ouml;pik') {
                $opik = $mp;
                continue;
            }
            if ($opik && strcmp('Opik', $mp['last_name']) < 0) {
                render_mps_row($opik, $style, $order, $MPURL);
                $opik = array();
            }
            render_mps_row($mp, $style, $order, $MPURL);

        }
        ?>
    </tbody>
</table>

<?php

function manymins($p, $d)
{
    return prettify_office($p, $d);
}

function render_mps_row($mp, &$style, $order, $MPURL)
{

    // Stripes
    $style = $style == '1' ? '2' : '1';

    $name = member_full_name(1, $mp['title'], $mp['first_name'], $mp['last_name'], $mp['constituency']);
    $url = $MPURL->generate() . make_member_url($mp['first_name'] . ' ' . $mp['last_name'], $mp['constituency'], 1);
    $name_safe = htmlentities($name);
    $party_safe = htmlentities($mp['party']);
    $constituency_safe = htmlentities($mp['constituency']);
    $url_safe = htmlentities($url);
    $photo_alt = htmlentities('Photo of ' . $name);

    #	$MPURL->insert(array('pid'=>$mp['person_id']));
    list($image, $sz) = find_rep_image($mp['person_id'], true);
    ?>
    <tr>
        <td class="row">
            <?php
            if ($image)
                echo '<a href="', $url_safe, '">',
                    '<img class="portrait" alt="', $photo_alt, '" loading="lazy" src="', htmlentities($image), '">',
                    '</a>';
            ?>
        </td>
        <td class="row-<?php echo $style; ?>"><a
                href="<?php echo $url_safe; ?>"><?php echo $name_safe; ?></a>
        </td>
        <td class="row-<?php echo $style; ?>"><?php echo $party_safe; ?></td>
        <td class="row-<?php echo $style; ?>"><?php echo $constituency_safe; ?></td>
        <td class="row-<?php echo $style; ?>">
            <?php
            if (is_array($mp['dept'])) {
                $positions = array_map('manymins', $mp['pos'], $mp['dept']);
                $positions = array_map('htmlentities', $positions);
                print join('<br>', $positions);
            } elseif ($mp['dept'] || $mp['pos']) {
                print htmlentities(prettify_office($mp['pos'], $mp['dept']));
            } else {
                print '&nbsp;';
            }
                    ?>
            </td>
        <?php if ($order == 'expenses') { ?>
            <td class="row-<?php echo $style; ?>">&pound;<?php echo number_format($mp['data_value']); ?></td>
        <?php } elseif ($order == 'debates') { ?>
            <td class="row-<?php echo $style; ?>"><?php echo number_format($mp['data_value']); ?></td>
        <?php } elseif ($order == 'safety') { ?>
            <td class="row-<?php echo $style; ?>"><?php echo htmlentities($mp['data_value']) ?></td>
        <?php }
            ?>
    </tr>
    <?php

}

?>
