<?php

/*
Used on the 'All Peers' page to produce the list of Peers.

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

global $this_page;

twfy_debug("TEMPLATE", "people_mps.php");

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

if ($order == 'party')
    $th_party = 'Party';

$URL->insert(array('o' => 'c'));
$th_state = '<a href="' . $URL->generate() . '">State</a>';
if ($order == 'constituency')
    $th_state = 'State';

// Toolbar
echo '<div class="people-toolbar" style="margin-bottom:0.75rem;font-size:0.95rem;color:#374151">Sort: ' . $th_name . ' | ' . $th_party . ' | ' . $th_state . '</div>';

echo '<div class="people-cards">';

$URL = new URL(str_replace('s', '', $this_page));
$style = '2';

foreach ($data['data'] as $pid => $peer) {
    render_peers_row($peer, $style, $order, $URL);
}

echo '</div>';

function manymins($p, $d)
{
    return prettify_office($p, $d);
}

function render_peers_row($peer, &$style, $order, $URL)
{
    global $parties;

    // Stripes
    $style = $style == '1' ? '2' : '1';

    $name = member_full_name(2, $peer['title'], $peer['first_name'], $peer['last_name'], $peer['constituency']);
    if (array_key_exists($peer['party'], $parties))
        $party = $parties[$peer['party']];
    else
        $party = $peer['party'];

    $url = $URL->generate() . make_member_url($name, $peer['constituency'], 2);

    list($image, $sz) = find_rep_image($peer['person_id'], true);

    echo '<article class="person-card">';
    echo '<div class="person-card-inner">';
    echo '<div class="person-avatar">';
    if ($image) echo '<a href="' . $url . '"><img class="portrait" alt="" src="' . $image . '"/></a>';
    echo '</div>';
    echo '<div class="person-main">';
    echo '<a class="person-name" href="' . $url . '">' . ucfirst($name) . '</a>';
    echo '<div class="person-meta"><span class="party">' . htmlspecialchars($party) . '</span> <span class="sep">·</span> <span class="constituency">' . htmlspecialchars($peer['constituency']) . '</span></div>';
    echo '<div class="person-positions">';
    if (is_array($peer['dept'])) {
        echo implode('<br/>', array_map('manymins', $peer['pos'], $peer['dept']));
    } elseif ($peer['dept']) {
        echo prettify_office($peer['pos'], $peer['dept']);
    } else {
        echo '&nbsp;';
    }
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '</article>';
}

?>
