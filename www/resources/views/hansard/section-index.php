<?php

/**
 * @file
 * A section-index page's list of topics - eg an Adjournment debate's own gid page,
 * where each MP's separate, unrelated topic ("Climate Change", "Humanitarian and
 * Refugee Visas", ...) links out to its own already-Plates-rendered transcript page,
 * rather than showing full speeches inline here (see hansard_gid.php's
 * $data['subrows'] handling). $items is a list of HansardSectionIndexItem (see
 * HansardSpeechView.php).
 */
?>
<ul class="list-none">
    <?php foreach ($items as $n => $item): ?>
        <?php
        // The excerpt is the *first* speech in this topic only (see
        // hansardlist.php's own excerpt query - ORDER BY hpos ASC LIMIT 1), not
        // something all the speakers below said together - $firstSpeakerName
        // labels the quote itself so it can't read as a group statement, and the
        // chips row below gets its own "Spoke on this topic" caption for the same
        // reason.
        ?>
        <?php $firstSpeakerName = $item->speakers[0]->name ?? null; ?>
        <li>
            <?php if ($n > 0): ?>
                <?php
                // border-0 border-t, not just a border-color utility: Tailwind's
                // preflight reset is off project-wide (tailwind.config.js), so <hr>
                // still carries the browser's own default styling here - a thick,
                // inset/3D-looking double line - see transcript.php's own header <hr>
                // for the same fix. A plain line between items, not a boxed/bordered
                // card around each one (which rendered with a stray black border -
                // Tailwind's bare "border" utility sets border-color: currentColor
                // with nothing here overriding it away from the default text colour).
                ?>
                <hr class="border-0 border-t border-slate-200">
            <?php endif; ?>
            <?php
            // !-prefixed classes: layout.css's legacy "a:link"/"a:visited" rules
            // (specificity 0,1,1) beat a plain Tailwind colour class (0,1,0)
            // regardless of stylesheet order - see speech.php's own comment on this.
            //
            // pr-8, and the chevron below: reserves room on the right for a hover-only
            // affordance, so nothing here needs to know the chevron exists to avoid
            // overlapping it - min-w-0 flex-1 on the title is what actually lets long
            // titles wrap instead of overflowing.
            ?>
            <?php if ($item->url): ?>
                <a href="<?php echo $this->e($item->url) ?>"
                    class="group relative block rounded-lg py-4 pl-2 pr-8 !no-underline transition-colors hover:bg-slate-50">
                    <div class="flex items-start justify-between gap-3">
                        <p class="min-w-0 flex-1 text-lg font-semibold !text-slate-900"><?php echo $item->titleHtml /* already-safe HTML, same source as the old stripe rendering */ ?></p>
                        <?php if ($item->countLabel): ?>
                            <span class="mt-0.5 shrink-0 whitespace-nowrap rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600"><?php echo $this->e($item->countLabel) ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ($item->excerptHtml): ?>
                        <p class="mt-2 border-0 border-l-2 border-slate-200 pl-3 text-sm italic text-slate-500">
                            <?php echo $item->excerptHtml /* already-safe HTML (trim_characters() output, may contain entities like &#8212; - echoed raw same as the old rendering, not re-escaped) */ ?>
                            <?php if ($firstSpeakerName): ?>
                                <cite class="mt-1 block not-italic text-xs font-medium text-slate-500">&mdash; <?php echo $this->e($firstSpeakerName) ?></cite>
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>
                    <?php if ($item->speakers): ?>
                        <p class="mt-3 text-xs font-medium uppercase tracking-wide text-slate-500">Spoke on this topic</p>
                        <?php echo $this->fetch('hansard/speaker-chips', ['speakers' => $item->speakers]) ?>
                    <?php endif; ?>
                    <?php
                    // Heroicons "chevron-right" (MIT) - a plain clickability hint, not
                    // a control of its own (aria-hidden, the whole row is already the
                    // one link). Vertically centred on the row, not just the title
                    // line, so it doesn't drift off-centre next to a two-line title.
                    ?>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"
                        class="absolute right-2 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-300 opacity-0 transition-opacity group-hover:opacity-100 group-hover:text-teal-600">
                        <path d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                </a>
            <?php else: ?>
                <div class="py-4 pl-2 pr-8">
                    <p class="text-lg font-semibold text-slate-900"><?php echo $item->titleHtml /* already-safe HTML, same source as the old stripe rendering */ ?></p>
                    <?php if ($item->excerptHtml): ?>
                        <p class="mt-2 border-0 border-l-2 border-slate-200 pl-3 text-sm italic text-slate-500">
                            <?php echo $item->excerptHtml /* already-safe HTML - see the <a> branch's comment above */ ?>
                            <?php if ($firstSpeakerName): ?>
                                <cite class="mt-1 block not-italic text-xs font-medium text-slate-500">&mdash; <?php echo $this->e($firstSpeakerName) ?></cite>
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>
                    <?php if ($item->speakers): ?>
                        <p class="mt-3 text-xs font-medium uppercase tracking-wide text-slate-500">Spoke on this topic</p>
                        <?php echo $this->fetch('hansard/speaker-chips', ['speakers' => $item->speakers]) ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </li>
    <?php endforeach; ?>
</ul>
