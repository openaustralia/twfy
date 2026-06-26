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
        $former = "";
        $left_house = $MEMBER->left_house();
        if ($left_house[1]['date'] != '9999-12-31') {
            $former = 'former';
        }
        ?>
        <p style="margin-top: -30px; margin-bottom: 5px">Find out more about <a
                href="<?php echo $MPURL->generate(); ?>"><strong><?php echo $mpname; ?>, your <?php echo $former ?>
                    Representative</strong></a>, including their <a href="<?php echo $MP_RECENT_URL->generate() ?>">most recent
                speeches</a>.</p>
        <p style="margin-bottom: 30px">If <?php echo $mpname; ?> is not your Representative, <a
                href="<?php echo $CHANGEURL->generate(); ?>">provide a new postcode</a>.</p>
        <?php
    }
} else {
    ?>
    <p style="margin-top: -30px; margin-bottom: 30px">Find out who <a href="<?php echo $MPURL->generate() ?>">your
            Representative</a> is. All you need is a postcode.</p>
    <?php
}

?>
<?php

$order = $data['info']['order'];

$URL = new URL($this_page);

// Build simple sort links for the people list toolbar
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
$URL->insert(array('o' => 'p'));
$th_party = '<a href="' . $URL->generate() . '">Party</a>';
$URL->insert(array('o' => 'c'));
$th_constituency = '<a href="' . $URL->generate() . '">Constituency</a>';

if ($order == 'party') {
    $th_party = 'Party';
} elseif ($order == 'constituency') {
    $th_constituency = 'Constituency';
}

// Toolbar with sort links (compact)
echo '<div class="people-toolbar" style="margin-bottom:0.75rem;font-size:0.95rem;color:#374151">Sort: ' . $th_name . ' | ' . $th_party . ' | ' . $th_constituency . '</div>';

// Card container (mobile-first)
echo '<div class="people-cards">';

$MPURL = new URL(str_replace('s', '', $this_page));
$style = '2';

// Preserve special ordering handling for Opik
$opik = array();
foreach ($data['data'] as $pid => $mp) {
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

echo '</div>';

function manymins($p, $d)
{
    return prettify_office($p, $d);
}

function render_mps_row($mp, &$style, $order, $MPURL)
{
    // Alternate stripe style (still available for desktop via background)
    $style = $style == '1' ? '2' : '1';

    $name = member_full_name(1, $mp['title'], $mp['first_name'], $mp['last_name'], $mp['constituency']);
    $url = $MPURL->generate() . make_member_url($mp['first_name'] . ' ' . $mp['last_name'], $mp['constituency'], 1);

    list($image, $sz) = find_rep_image($mp['person_id'], true);

    echo '<article class="person-card">';
    echo '<div class="person-card-inner">';
    echo '<div class="person-avatar">';
    if ($image) echo '<a href="' . $url . '"><img class="portrait" alt="" src="' . $image . '"/></a>';
    echo '</div>';
    echo '<div class="person-main">';
    echo '<a class="person-name" href="' . $url . '">' . $name . '</a>';
    echo '<div class="person-meta"><span class="party">' . htmlspecialchars($mp['party']) . '</span> <span class="sep">·</span> <span class="constituency">' . htmlspecialchars($mp['constituency']) . '</span></div>';
    echo '<div class="person-positions">';
    if (is_array($mp['dept'])) {
        echo implode('<br/>', array_map('manymins', $mp['pos'], $mp['dept']));
    } elseif ($mp['dept'] || $mp['pos']) {
        echo prettify_office($mp['pos'], $mp['dept']);
    } else {
        echo '&nbsp;';
    }
    echo '</div>'; // positions
    echo '</div>'; // main
    echo '</div>'; // inner
    echo '</article>';
}

?>
