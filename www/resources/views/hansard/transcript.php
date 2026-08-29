<?php

/**
 * @file
 * Top-level template for the House/Senate debate transcript (see hansard_gid.php,
 * major 1/101 only). Receives $items: a flat list of HansardSpeechView and
 * HansardProceduralView objects (see HansardSpeechView.php) in page order - all the
 * looping and per-row dispatch lives here, not in the calling PHP, so the calling
 * code's only job is building that list of plain data objects.
 */
?>
<div class="bg-slate-50 p-3 md:p-6 rounded-2xl">
    <?php
    // lg:grid puts the transcript and the "Speakers in this debate" roster
    // side-by-side, matching the mockup - both sit in the same grey backdrop rather
    // than the roster looking like a bolted-on afterthought. Below lg they stack, card
    // then roster, in source order.
    ?>
    <div class="lg:grid lg:grid-cols-3 lg:gap-6 lg:items-start">
    <div class="lg:col-span-2 bg-white rounded-2xl shadow-lg p-6 md:p-8 space-y-8">
        <div>
            <?php
            // "House debates"/"Senate debates" - was its own heading above the card
            // (see the $PAGE->heading_displayed suppression in hansard_gid.php); now
            // sits next to the section title instead.
            ?>
            <?php if ($chamberLabel || $sectionTitle): ?>
                <p class="text-sm font-semibold uppercase tracking-wide text-teal-700">
                    <?php if ($chamberLabel): ?>
                        <?php echo $this->e($chamberLabel) ?>
                    <?php endif; ?>
                    <?php if ($chamberLabel && $sectionTitle): ?>
                        <span aria-hidden="true" class="text-teal-700/50">&middot;</span>
                    <?php endif; ?>
                    <?php if ($sectionTitle): ?>
                        <?php echo $sectionTitle /* already-safe HTML, same source as the old stripe-head-2 heading */ ?>
                    <?php endif; ?>
                </p>
            <?php endif; ?>
            <h1 class="text-3xl md:text-4xl font-bold text-slate-900 leading-tight"><?php echo $subsectionTitle /* already-safe HTML, same source as the old stripe-head-2 heading */ ?></h1>
            <div class="mt-3 flex items-center gap-4 text-sm text-slate-600">
                <?php if ($dateUrl): ?>
                    <a href="<?php echo $this->e($dateUrl) ?>" class="hover:text-teal-700"
                        title="See all debates on this date"><?php echo $this->e($date) ?></a>
                <?php else: ?>
                    <span><?php echo $this->e($date) ?></span>
                <?php endif; ?>
                <?php if ($chamber): ?>
                    <span aria-hidden="true">&middot;</span>
                    <?php
                    // Heroicons "building-library" (MIT) - a columned building, the
                    // closest free equivalent to Font Awesome's building-columns glyph,
                    // inlined so there's no icon-font/CDN dependency.
                    ?>
                    <span class="inline-flex items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
                            class="w-4 h-4 text-slate-400" aria-hidden="true">
                            <path d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0 0 12 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75Z" />
                        </svg>
                        <?php echo $this->e($chamber) ?>
                    </span>
                <?php endif; ?>
            </div>
            <?php
            // border-0 border-t, not just a border-color utility: Tailwind's preflight
            // reset is off project-wide (tailwind.config.js), so <hr> still carries the
            // browser's own default styling here - a thick, inset/3D-looking double
            // line, not the clean flat rule Tailwind users normally take for granted.
            // The mockup sidesteps this entirely with a plain border-b div instead of a
            // real <hr>; resetting the native borders first gets the same flat look
            // while keeping the semantic element.
            ?>
            <hr class="mt-4 border-0 border-t border-slate-200">
        </div>

        <?php foreach ($items as $item): ?>
            <?php if ($item instanceof HansardSpeechView): ?>
                <?php echo $this->fetch('hansard/speech', ['speech' => $item]) ?>
            <?php elseif ($item instanceof HansardProceduralView): ?>
                <?php echo $this->fetch('hansard/procedural', ['item' => $item]) ?>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <?php if ($aboutBodyHtml || !empty($speakers)): ?>
        <?php
        // One grid item holding both right-column cards, stacked - not two separate
        // col-span-1 siblings. CSS grid auto-placement would otherwise drop the second
        // one into row 2's *first* column (under the transcript card, not under the
        // first sidebar card), since nothing here pins it back to column 3.
        ?>
        <div class="lg:col-span-1 space-y-6">
            <?php if ($aboutBodyHtml): ?>
                <?php echo $this->fetch('hansard/about-debates', ['title' => $aboutTitle, 'bodyHtml' => $aboutBodyHtml]) ?>
            <?php endif; ?>
            <?php if (!empty($speakers)): ?>
                <?php echo $this->fetch('hansard/speaker-roster', ['speakers' => $speakers]) ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    </div>
</div>
