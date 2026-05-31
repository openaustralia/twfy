<?php

# Used on the 'All MLAs' page to produce the list of MLAs.

global $this_page;

twfy_debug("TEMPLATE", "people_msps.php");

$order = $data['info']['order'];

$URL = new URL($this_page);

if ($order == 'first_name') {
    $th_name = 'First';
} else {
    $URL->insert(array('o' => 'f'));
    $th_name = '<a href="' . htmlentities($URL->generate()) . '">First</a>';
}
$th_name .= ' &amp; ';
if ($order == 'last_name') {
    $th_name .= 'Last';
} else {
    $URL->insert(array('o' => 'l'));
    $th_name .= '<a href="' . htmlentities($URL->generate()) . '">Last</a>';
}
$th_name .= ' name';
$URL->insert(array('o' => 'p'));
$th_party = '<a href="' . htmlentities($URL->generate()) . '">Party</a>';
$URL->insert(array('o' => 'c'));
$th_constituency = '<a href="' . htmlentities($URL->generate()) . '">Constituency</a>';

if ($order == 'party') {
    $th_party = 'Party';
} elseif ($order == 'constituency') {
    $th_constituency = 'Constituency';
}

?>
<table border="0" cellpadding="4" cellspacing="0" width="90%" class="people oa-people-table">
    <caption class="oa-sr-only">List of MSPs by name, party, and constituency</caption>
    <thead>
        <tr>
            <th scope="col"><?php echo $th_name; ?></th>
            <th scope="col"><?php echo $th_party; ?></th>
            <th scope="col"><?php echo $th_constituency; ?></th>
        </tr>
    </thead>
    <tbody>
        <?php

        $MPURL = new URL(substr($this_page, 0, -1));
        $style = '2';
        foreach ($data['data'] as $pid => $mp) {
            render_mps_row($mp, $style, $order, $MPURL);
        }
        ?>
    </tbody>
</table>
<?php

function render_mps_row($mp, &$style, $order, $MPURL)
{
    $style = $style == '1' ? '2' : '1';
    $name = member_full_name(4, $mp['title'], $mp['first_name'], $mp['last_name'], $mp['constituency']);
    $url = $MPURL->generate() . make_member_url($mp['first_name'] . ' ' . $mp['last_name'], $mp['constituency'], 4);
    $name_safe = htmlentities($name);
    $url_safe = htmlentities($url);
    $party_safe = htmlentities($mp['party']);
    $constituency_safe = htmlentities($mp['constituency']);
    ?>
    <tr>
        <td class="row-<?php echo $style; ?>"><a href="<?php echo $url_safe; ?>"><?php echo $name_safe; ?></a></td>
        <td class="row-<?php echo $style; ?>"><?php echo $party_safe; ?></td>
        <td class="row-<?php echo $style; ?>"><?php echo $constituency_safe; ?></td>
    </tr>
    <?php

}

?>
