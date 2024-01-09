<?php
// This sidebar is on the very front page of the site.

$this->block_start(array('id'=>'help', 'title'=>"What's all this about?"));

$URL = new URL('about');
$abouturl = $URL->generate();

$URL = new URL('help');
$helpurl = $URL->generate();
?>

<p><a href="https://donate.oaf.org.au/">
        <img src="<?=IMAGEPATH."donate_greenL.png"?>" width="108" height="43" border="0" align="right" hspace="4"
            vspace="5" alt="Donate"></a>
    <a href="<?php echo $abouturl; ?>" title="link to About Us page">OpenAustralia.org</a> is a
    non-partisan website run by a charity, the <a href="http://www.openaustraliafoundation.org.au">OpenAustralia
        Foundation</a>. It aims to make it easy for people to keep tabs on their
    representatives in Parliament.</p>
<p>The OpenAustralia Foundation is an independent, strictly non-partisan <a
        href="https://www.acnc.gov.au/charity/55c2c06e21ac71e9359a0590b9fc100e">charity</a>, powered by donations from
    people like you.</p>

<?php
$this->block_end();
?>