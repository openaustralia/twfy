<?php

/**
 * @file
 * "All Senate debates on 18 August 2026 / « Previous debate / Next debate »" - was
 * the stripe-foot block at the bottom of the page (see the guarded
 * stripe_start('foot') in hansard_gid.php); its own block in the right-hand column
 * here instead. $nextPrev has up to three keys - 'prev', 'up', 'next' - each optional,
 * each ['label' => ..., 'url' => ...|null, 'title' => ...]; a present entry with no
 * url is plain text, not a link (matches $PAGE->nextprevlinks()'s own fallback).
 *
 * !-prefixed classes throughout: layout.css has legacy "a:link { color: #00b;
 * text-decoration: underline }" / "a:visited { color: #505; ... }" rules
 * (specificity 0,1,1) that beat a plain Tailwind colour class (0,1,0) regardless of
 * stylesheet order - same fix PR #225's nav bar already uses (see page.php).
 */
?>
<aside class="bg-white rounded-2xl shadow-lg p-6 md:p-8">
    <h2 class="text-base font-semibold uppercase tracking-wide text-teal-700 mt-0 mb-4 mx-0">More debates</h2>
    <div class="space-y-3 text-sm">
        <?php if (isset($nextPrev['up'])): ?>
            <div>
                <?php if ($nextPrev['up']['url']): ?>
                    <a href="<?php echo $this->e($nextPrev['up']['url']) ?>" class="!text-slate-700 hover:!text-teal-700 !no-underline"
                        title="<?php echo $this->e($nextPrev['up']['title']) ?>"><?php echo $this->e($nextPrev['up']['label']) ?></a>
                <?php else: ?>
                    <span class="text-slate-700"><?php echo $this->e($nextPrev['up']['label']) ?></span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <?php if (isset($nextPrev['prev']) || isset($nextPrev['next'])): ?>
            <div class="flex items-center justify-between gap-4 pt-3 border-0 border-t border-slate-200">
                <?php if (isset($nextPrev['prev'])): ?>
                    <?php if ($nextPrev['prev']['url']): ?>
                        <a href="<?php echo $this->e($nextPrev['prev']['url']) ?>" class="!text-slate-700 hover:!text-teal-700 !no-underline"
                            title="<?php echo $this->e($nextPrev['prev']['title']) ?>">&laquo; <?php echo $this->e($nextPrev['prev']['label']) ?></a>
                    <?php else: ?>
                        <span class="text-slate-400">&laquo; <?php echo $this->e($nextPrev['prev']['label']) ?></span>
                    <?php endif; ?>
                <?php else: ?>
                    <span></span>
                <?php endif; ?>
                <?php if (isset($nextPrev['next'])): ?>
                    <?php if ($nextPrev['next']['url']): ?>
                        <a href="<?php echo $this->e($nextPrev['next']['url']) ?>" class="!text-slate-700 hover:!text-teal-700 !no-underline"
                            title="<?php echo $this->e($nextPrev['next']['title']) ?>"><?php echo $this->e($nextPrev['next']['label']) ?> &raquo;</a>
                    <?php else: ?>
                        <span class="text-slate-400"><?php echo $this->e($nextPrev['next']['label']) ?> &raquo;</span>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</aside>
