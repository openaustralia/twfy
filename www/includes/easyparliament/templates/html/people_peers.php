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

twfy_debug("TEMPLATE", "people_peers.php");

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

?>
<table border="0" cellpadding="4" cellspacing="0" width="90%" class="people oa-people-table">
    <caption class="oa-sr-only">List of peers, including party, state, and positions</caption>
    <thead>
        <tr>
            <th scope="col">Photo</th>
            <th scope="col"><?php echo $th_name; ?></th>
            <th scope="col"><?php echo $th_party; ?></th>
            <th scope="col"><?php echo $th_state; ?></th>
            <th scope="col">Positions</th>
            <?php if ($order == 'debates') { ?>
                <th scope="col">Debates spoken in the last year</th>
            <?php } ?>
        </tr>
    </thead>
    <tbody>
        <?php

        $URL = new URL(str_replace('s', '', $this_page));
        $style = '2';

        foreach ($data['data'] as $pid => $peer) {
            render_peers_row($peer, $style, $order, $URL);
        }
        ?>
    </tbody>
</table>

<?php

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
    $name_title = ucfirst($name);
    if (array_key_exists($peer['party'], $parties))
        $party = $parties[$peer['party']];
    else
        $party = $peer['party'];

    #	$MPURL->insert(array('pid'=>$peer['person_id']));
    $url = $URL->generate() . make_member_url($name, $peer['constituency'], 2);
    $url_safe = htmlentities($url);
    $name_safe = htmlentities($name_title);
    $party_safe = htmlentities($party);
    $state_safe = htmlentities($peer['constituency']);
    $photo_alt = htmlentities('Photo of ' . $name_title);
    ?>
    <tr>
        <td class="row">
            <?php
            list($image, $sz) = find_rep_image($peer['person_id'], true);
            if ($image) {
                echo '<a href="', $url_safe, '">';
                echo '<img class="portrait" alt="', $photo_alt, '" loading="lazy" src="', htmlentities($image), '"/>';
                echo '</a>';
            }
            ?>
        </td>
        <td class="row-<?php echo $style; ?>">
            <a href="<?php echo $url_safe; ?>"><?php echo $name_safe; ?></a>
        </td>
        <td class="row-<?php echo $style; ?>"><?php echo $party_safe; ?></td>
        <td class="row-<?php echo $style; ?>"><?php echo $state_safe ?></td>
        <td class="row-<?php echo $style; ?>">
            <?php
            if (is_array($peer['dept'])) {
                $positions = array_map('manymins', $peer['pos'], $peer['dept']);
                $positions = array_map('htmlentities', $positions);
                print implode('<br>', $positions);
            } elseif ($peer['dept']) {
                print htmlentities(prettify_office($peer['pos'], $peer['dept']));
            } else {
                print '&nbsp;';
            }
            ?>
            </td>

        <?php if ($order == 'debates') { ?>
            <td class="row-<?php echo $style; ?>"><?php echo number_format($peer['data_value']); ?></td>
        <?php } ?>

    </tr>
    <?php

}

?>
