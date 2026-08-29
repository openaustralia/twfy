<?php

/**
 * @file
 * "🔎 Search Hansard" homepage hero. $searchUrl is the /search/ form action,
 * $keyword pre-fills the input from ?keyword= (used when arriving from an email
 * alert link), $popularSearchesLabel is FrontPageView::popularSearchesLabel()'s
 * output - null when there's nothing to show yet.
 */
?>
<section class="mx-4 mb-8 box-border rounded-lg bg-[#26343b] p-6 text-white shadow-md md:mx-8 md:p-8">
    <h2 class="!text-white mb-2 text-2xl font-semibold">🔎 Search Hansard</h2>
    <?php
    // text-lg: matches the body-copy size introduced on the debate transcript page
    // (resources/views/hansard/speech.php) - was unstyled before (so whatever size
    // global.css's 80%-scaled body default worked out to).
    ?>
    <p class="mb-5 text-lg text-slate-200">Find speeches, debates and decisions from Australia's Federal Parliament.</p>
    <form action="<?php echo $this->e($searchUrl) ?>" method="get" class="flex flex-col gap-3 sm:flex-row">
        <label for="hero-search" class="sr-only">Search Hansard</label>
        <input type="text" name="s" id="hero-search" maxlength="100" class="!m-0 !min-w-0 !w-full box-border rounded border-0 px-4 py-3 text-base text-slate-900 shadow-sm" value="<?php echo $this->e($keyword) ?>" placeholder="Search by topic, person or phrase">
        <?php
        // bg-teal-700, not teal-600: white-on-teal-600 is 3.74:1 (axe-core
        // color-contrast, needs 4.5:1 for normal-weight text) - teal-700 clears it.
        ?>
        <button type="submit" class="rounded bg-teal-700 px-6 py-3 font-semibold text-white shadow-sm hover:bg-teal-600">Search</button>
    </form>
    <?php if ($popularSearchesLabel !== null): ?>
        <p class="!mb-0 !mt-4 text-sm text-slate-200 [&_a]:!text-teal-200 [&_a:hover]:!text-white">Popular searches today: <?php echo $popularSearchesLabel /* already-safe HTML, same source as the old rendering - see FrontPageView::popularSearchesLabel() */ ?></p>
    <?php endif; ?>
</section>
