<?php

/**
 * @file
 * Prev/all/next pagination bar, above the transcript card - was the stripe-foot block
 * at the bottom of the page (see the guarded stripe_start('foot') in hansard_gid.php),
 * then briefly its own "More debates" card in the right-hand column, now here instead
 * since it's pagination and reads more like one at the top. $nextPrev has up to three
 * keys - 'prev', 'up', 'next' - each optional, each ['label' => ..., 'url' => ...|null,
 * 'title' => ...]; a present entry with no url is plain text, not a link (matches
 * $PAGE->nextprevlinks()'s own fallback).
 *
 * !-prefixed classes: layout.css has legacy "a:link { color: #00b; text-decoration:
 * underline }" / "a:visited { color: #505; ... }" rules (specificity 0,1,1) that beat
 * a plain Tailwind colour class (0,1,0) regardless of stylesheet order - same fix
 * PR #225's nav bar already uses (see page.php).
 */
?>
<nav class="flex items-center justify-between gap-4 mb-4 px-1 text-sm" aria-label="Debate navigation">
    <div class="flex-1 text-left">
        <?php if (isset($nextPrev['prev'])): ?>
            <?php if ($nextPrev['prev']['url']): ?>
                <a href="<?php echo $this->e($nextPrev['prev']['url']) ?>" class="!text-slate-700 hover:!text-teal-700 !no-underline"
                    title="<?php echo $this->e($nextPrev['prev']['title']) ?>">⬅️ <?php echo $this->e($nextPrev['prev']['label']) ?></a>
            <?php else: ?>
                <span class="text-slate-400">⬅️ <?php echo $this->e($nextPrev['prev']['label']) ?></span>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <div class="flex-1 text-center">
        <?php if (isset($nextPrev['up'])): ?>
            <?php if ($nextPrev['up']['url']): ?>
                <a href="<?php echo $this->e($nextPrev['up']['url']) ?>" class="!text-slate-700 hover:!text-teal-700 !no-underline"
                    title="<?php echo $this->e($nextPrev['up']['title']) ?>"><?php echo $this->e($nextPrev['up']['label']) ?></a>
            <?php else: ?>
                <span class="text-slate-700"><?php echo $this->e($nextPrev['up']['label']) ?></span>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <div class="flex-1 text-right">
        <?php if (isset($nextPrev['next'])): ?>
            <?php if ($nextPrev['next']['url']): ?>
                <a href="<?php echo $this->e($nextPrev['next']['url']) ?>" class="!text-slate-700 hover:!text-teal-700 !no-underline"
                    title="<?php echo $this->e($nextPrev['next']['title']) ?>"><?php echo $this->e($nextPrev['next']['label']) ?> ➡️</a>
            <?php else: ?>
                <span class="text-slate-400"><?php echo $this->e($nextPrev['next']['label']) ?> ➡️</span>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</nav>
