<?php

/**
 * @file
 * Top-level template for a section-index page (see hansard_gid.php's
 * $data['subrows'] handling) - eg an Adjournment debate's own gid page, where each
 * MP's separate topic links out to its own already-Plates-rendered transcript page
 * rather than showing content here. Same outer shell as transcript.php (grey
 * backdrop, white card, optional sidebar column), with hansard/section-index.php's
 * card list standing in for the flow of speeches, and - only when this section is
 * the Adjournment itself - an "About the Adjournment" card in the sidebar.
 */
?>
<div class="bg-slate-50 p-3 md:p-6 rounded-2xl">
    <div class="lg:grid lg:grid-cols-3 lg:gap-6 lg:items-start">
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-lg p-6 md:p-8 space-y-8">
            <div>
                <h1 class="text-3xl md:text-4xl font-bold text-slate-900 leading-tight"><?php echo $sectionTitle /* already-safe HTML, same source as the old stripe-head-2 heading */ ?></h1>
                <?php
                // Same eyebrow-label idea as transcript.php's own chamberLabel line
                // (no separate $sectionTitle here to join it to with a "·" - the
                // section title above already is this page's own $sectionTitle).
                ?>
                <?php if ($chamberLabel): ?>
                    <p class="mt-1 text-sm font-semibold uppercase tracking-wide text-teal-700"><?php echo $this->e($chamberLabel) ?></p>
                <?php endif; ?>
                <?php
                // Date + chamber row - same markup as transcript.php's own, which
                // this page didn't have at all before ("which house was this in, and
                // when?" wasn't answered anywhere on the page).
                ?>
                <div class="mt-3 flex items-center gap-4 text-sm text-slate-600">
                    <?php if ($dateUrl): ?>
                        <?php
                        // !-prefixed: layout.css's legacy "a:link"/"a:visited" rules
                        // (specificity 0,1,1) beat a plain Tailwind colour class
                        // (0,1,0) regardless of stylesheet order - see speech.php's
                        // own comment on this. Without it, this rendered classic
                        // underlined blue.
                        ?>
                        <a href="<?php echo $this->e($dateUrl) ?>" class="!text-slate-600 hover:!text-teal-700 !no-underline"
                            title="See all debates on this date"><?php echo $this->e($date) ?></a>
                    <?php else: ?>
                        <span><?php echo $this->e($date) ?></span>
                    <?php endif; ?>
                    <?php if ($chamber): ?>
                        <span aria-hidden="true">&middot;</span>
                        <?php
                        // Heroicons "building-library" (MIT) - same icon
                        // transcript.php's own chamber row uses.
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
                <hr class="mt-4 border-0 border-t border-slate-200">
            </div>

            <?php if (!empty($nextPrev)): ?>
                <?php echo $this->fetch('hansard/pagination', ['nextPrev' => $nextPrev, 'edge' => 'top']) ?>
            <?php endif; ?>

            <?php echo $this->fetch('hansard/section-index', ['items' => $items]) ?>

            <?php if (!empty($nextPrev)): ?>
                <?php echo $this->fetch('hansard/pagination', ['nextPrev' => $nextPrev, 'edge' => 'bottom']) ?>
            <?php endif; ?>
        </div>

        <?php if ($aboutBodyHtml): ?>
            <div class="lg:col-span-1 space-y-6">
                <?php echo $this->fetch('hansard/about-debates', ['title' => $aboutTitle, 'bodyHtml' => $aboutBodyHtml]) ?>
            </div>
        <?php endif; ?>
    </div>
</div>
