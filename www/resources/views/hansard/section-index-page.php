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
                <?php
                // Same eyebrow-label idea as transcript.php's own chamberLabel line -
                // "which house was this in?" wasn't shown anywhere on this page
                // before (unlike the transcript card, which already had it).
                ?>
                <?php if ($chamberLabel): ?>
                    <p class="text-sm font-semibold uppercase tracking-wide text-teal-700"><?php echo $this->e($chamberLabel) ?></p>
                <?php endif; ?>
                <h1 class="text-3xl md:text-4xl font-bold text-slate-900 leading-tight"><?php echo $sectionTitle /* already-safe HTML, same source as the old stripe-head-2 heading */ ?></h1>
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
