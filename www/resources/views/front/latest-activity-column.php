<?php

/**
 * @file
 * One "Latest Activity" column (House or Senate) - every top-level section from
 * the most recent sitting day, start of day first. $items is a list of
 * ['title' => already-safe HTML, 'speakers' => list of HansardSpeakerRosterEntry
 * (see HansardSpeechView::speakerEntry()), 'moreSpeakersCount' => int, 'topics' =>
 * list of ['title' => already-safe HTML, 'url' => plain URL], 'moreTopicsCount' =>
 * int] - built by www/docs/index.php's own latest_activity_items(), which is
 * DB-coupled so stays there rather than moving into FrontPageView.php alongside
 * the DB-free logic (topics themselves are deduped/capped by the DB-free
 * FrontPageView::summarizeTopics()). Can run to 20-30 items on a busy sitting
 * day, so each row is a plain compact list item rather than the padded card a
 * short top-5 list could afford.
 *
 * 'title' is just the procedural heading ("Bills", "Committees", ...) - a label,
 * not a link (a whole top-level section rarely has anywhere more specific to
 * point at than its own topics do). Every actual link here is on something
 * specific instead: each topic to its own subsection page, each speaker chip
 * (hansard/speaker-chips.php - the same "who spoke on this topic" component the
 * section-index page uses) to where they first speak, via its own
 * $speaker->firstSpeechUrl.
 */
?>
<div>
    <?php
    // Heroicons "building-library" (MIT) - same icon the debate transcript page uses
    // next to the chamber name (see resources/views/hansard/transcript.php).
    ?>
    <h3 class="mb-3 flex items-center gap-2 text-lg font-semibold text-slate-900">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
            stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
            class="h-5 w-5 <?php echo $this->e($iconColorClass) ?>" aria-hidden="true">
            <path d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0 0 12 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75Z" />
        </svg>
        <?php echo $this->e($chamberName) ?>
        <?php
        // House and Senate don't always sit on the same day - see index.php's own
        // latest_activity_column(), which passes each column its own $recent
        // date rather than one shared page-level one.
        ?>
        <span class="text-sm font-normal text-slate-500">&middot; <?php echo $this->e($date) ?></span>
    </h3>
    <?php
    // [&>li]:!m-0: layout.css's bare "li { margin: 0 0 0.5em 1.2em }" rule
    // otherwise indents every row and adds an extra bottom margin on top of
    // this list's own divide-y/space-y-* spacing. Copilot review on #228.
    ?>
    <ul class="list-none divide-y divide-solid divide-slate-100 border-y border-solid border-slate-100 [&>li]:!m-0">
        <?php foreach ($items as $item): ?>
            <li class="px-2 py-2.5">
                <p class="font-semibold text-slate-900"><?php echo $item['title'] /* already-safe HTML, same source as the old rendering */ ?></p>
                <?php if ($item['topics']): ?>
                    <ul class="mt-1 list-none space-y-0.5 [&>li]:!m-0">
                        <?php foreach ($item['topics'] as $topic): ?>
                            <li class="text-sm">
                                <?php
                                // !-prefixed: see speaker-chips.php's own comment
                                // on the same legacy a:link/a:visited specificity
                                // issue - matters here too even without a
                                // surrounding <a> any more, since layout.css's
                                // rule is unscoped to begin with.
                                ?>
                                <a href="<?php echo $this->e($topic['url']) ?>" class="!text-slate-600 hover:!text-teal-700 !no-underline hover:!underline"><?php echo $topic['title'] /* already-safe HTML, same source as $item['title'] */ ?></a>
                            </li>
                        <?php endforeach; ?>
                        <?php if ($item['moreTopicsCount'] > 0): ?>
                            <li class="text-xs font-medium !text-slate-500">+<?php echo (int) $item['moreTopicsCount'] ?> more</li>
                        <?php endif; ?>
                    </ul>
                <?php endif; ?>
                <?php if ($item['speakers']): ?>
                    <?php echo $this->fetch('hansard/speaker-chips', ['speakers' => $item['speakers'], 'moreCount' => $item['moreSpeakersCount']]) ?>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
    <a href="<?php echo $this->e($dayUrl) ?>" class="mt-4 inline-block font-semibold !text-teal-800 hover:!text-teal-600 !no-underline">View
        all from <?php echo $this->e($viewAllLabel) ?> &rarr;</a>
</div>
