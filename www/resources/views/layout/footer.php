<?php

/**
 * @file
 * The site-wide footer (PAGE::content_end(), every page) - about blurb, Help/
 * Developers link lists, a donate callout, and the "Other Wonderful Projects"
 * strip. $helpLinks/$devLinks are already-safe HTML (a title, or an <a> already
 * built by content_end() - some entries are plain text, eg the current page's own
 * title in $helpLinks, not linked to itself).
 *
 * bg-[#26343b]: the same navy as the nav bar and the front page's search hero (see
 * docs/index.php's search_hero()) - matches Donna's homepage mockup's own dark
 * footer (https://kattekrab.github.io/openausmockup.html), just in this site's
 * navy rather than the mockup's plain gray-800. Link colours throughout:
 * !text-teal-300 at rest, hover:!text-white, same convention search_hero()'s
 * "Popular searches" links already use on this navy - !-prefixed because
 * layout.css's legacy "a:link"/"a:visited" rules (specificity 0,1,1) beat a plain
 * colour class (0,1,0) regardless of stylesheet order. The old footer never had
 * this fix, so every link in it rendered classic underlined blue against a light
 * grey background - not actually this site's colours at all.
 */
?>
<div id="footer" class="!box-border !m-0 !mt-auto !bg-transparent !p-0 max-md:!w-screen">
    <div class="!box-border max-md:!w-screen bg-[#26343b] px-4 py-10 text-white md:px-8">
        <div class="grid gap-8 md:grid-cols-[2fr_1fr_1fr_1fr]">
            <div>
                <h2 class="text-lg font-semibold !text-white">OpenAustralia.org.au</h2>
                <p class="mt-2 text-sm text-slate-300">Keeping tabs on your representatives in the
                    Australian Parliament. A project by the OpenAustralia Foundation.</p>
                <?php
                // No [&_a]:!no-underline here: axe-core's link-in-text-block rule
                // (rightly) flags inline links sitting in a run of body text as needing
                // more than colour alone to be distinguishable - underline is the
                // simplest way to keep that. The Help/Developers lists below don't have
                // this problem (each link is a whole list item, not embedded in a
                // longer sentence), so those keep no-underline.
                ?>
                <p class="mt-2 text-sm text-slate-300 [&_a]:!text-teal-300 [&_a:hover]:!text-white">The
                    <a href="https://www.oaf.org.au">OpenAustralia Foundation</a>
                    is a public digital online library; independent and strictly non-partisan. As a
                    <a href="https://www.acnc.gov.au/charity/55c2c06e21ac71e9359a0590b9fc100e">registered
                        charity</a>, it is powered by donations from people like you.</p>
            </div>
            <div>
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-400">Help</h2>
                <ul class="mt-3 list-none space-y-2 text-sm [&_a]:!text-teal-300 [&_a:hover]:!text-white [&_a]:!no-underline">
                    <?php foreach ($helpLinks as $link): ?>
                    <li><?php echo $link /* already-safe HTML - see the @file comment above */ ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div>
                <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-400">Developers</h2>
                <ul class="mt-3 list-none space-y-2 text-sm [&_a]:!text-teal-300 [&_a:hover]:!text-white [&_a]:!no-underline">
                    <?php foreach ($devLinks as $link): ?>
                    <li><?php echo $link /* already-safe HTML - see the @file comment above */ ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="rounded-lg bg-white p-5 shadow-sm">
                <p class="text-sm text-slate-600">Your donations keep this site and others like
                    it running.</p>
                <a class="mt-3 inline-block rounded bg-teal-700 px-4 py-2 font-semibold !text-white !no-underline shadow-sm hover:bg-teal-600" href="https://donate.oaf.org.au/">Donate now ❤️</a>
            </div>
        </div>
        <div class="mt-10 border-t border-solid border-slate-600 pt-6 text-center text-sm text-slate-400 [&_a]:!text-teal-300 [&_a:hover]:!text-white">
            <p>Other Wonderful Projects from the OpenAustralia Foundation:
                <a href="https://theyvoteforyou.org.au/">They Vote For You</a> |
                <a href="https://www.righttoknow.org.au/">Right To Know</a> |
                <a href="http://www.planningalerts.org.au/">PlanningAlerts</a>
            </p>
        </div>
    </div>
</div>
