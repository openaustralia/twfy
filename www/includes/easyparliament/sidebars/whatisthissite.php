<?php

/**
 * @file
 * This sidebar is on the very front page of the site.
 */

$this->block_start([
    'id' => 'help',
    'title' => "What's all this about?",
    'class' => '!box-border !w-[calc(100vw-2rem)]',
]);

$URL = new URL('about');
$abouturl = $URL->generate();
?>

<p><strong>Hansard, made findable.</strong> Searchable transcripts of the Australian Federal Parliament.</p>

<p><a href="<?php echo $abouturl; ?>" title="link to About Us page">OpenAustralia.org.au</a> is an independent
    collection of Hansard, the official record of the Australian Federal Parliament. The Hansard library at
    OpenAustralia.org.au is stored as a machine-readable and searchable repository, providing democratic open access to
    Australia's legislative systems.</p>

<p>The <a href="https://www.oaf.org.au">OpenAustralia Foundation</a> is a public digital online library; independent and
    strictly non-partisan. As a <a
        href="https://www.acnc.gov.au/charity/55c2c06e21ac71e9359a0590b9fc100e">registered charity</a>, it is powered by
    donations from people like you.</p>

<div class="mt-5 border-t border-slate-200 pt-4">
    <p class="mb-3 text-sm text-slate-600">Help keep Australia's parliamentary record open and searchable.</p>
    <a class="inline-block rounded bg-teal-700 px-4 py-2 font-semibold !text-white no-underline shadow-sm hover:bg-teal-600" href="https://donate.oaf.org.au/">Support OpenAustralia.org</a>
</div>

<?php
$this->block_end();
