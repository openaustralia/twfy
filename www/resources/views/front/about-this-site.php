<?php

/**
 * @file
 * "We're a small charity with a big mission." - the mockup's own bottom-of-page
 * charity callout. $aboutUrl is the /about/ page URL.
 */
?>
<section class="mx-4 mb-12 rounded-2xl bg-slate-50 px-4 py-12 md:mx-8 md:px-8">
    <div class="mx-auto max-w-2xl rounded-2xl bg-white p-6 text-center shadow-md md:p-10">
        <h2 class="mb-4 text-2xl font-bold text-slate-900">We're a small charity with a big mission.</h2>
        <div class="space-y-4 text-lg text-slate-700">
            <p><strong>Hansard, made findable.</strong> Searchable transcripts of the Australian Federal Parliament.</p>
            <p><a href="<?php echo $this->e($aboutUrl) ?>" class="!text-teal-800 hover:!text-teal-600" title="link to About Us page">OpenAustralia.org.au</a> is
                an independent collection of Hansard, the official record of the Australian Federal Parliament.
                The <a href="https://www.oaf.org.au" class="!text-teal-800 hover:!text-teal-600">OpenAustralia Foundation</a> is a public
                digital online library; independent and strictly non-partisan. As a <a href="https://www.acnc.gov.au/charity/55c2c06e21ac71e9359a0590b9fc100e" class="!text-teal-800 hover:!text-teal-600">registered
                    charity</a>, it is powered by donations from people like you.</p>
        </div>
        <?php
        // hover:bg-teal-800, not teal-600 - white-on-teal-600 is 3.74:1, below the
        // 4.5:1 AA threshold for normal-weight text. Copilot review on #228.
        ?>
        <div class="mt-6">
            <a class="inline-block rounded bg-teal-700 px-6 py-3 font-semibold !text-white !no-underline shadow-sm hover:bg-teal-800" href="https://donate.oaf.org.au/">Support OpenAustralia.org</a>
        </div>
    </div>
</section>
