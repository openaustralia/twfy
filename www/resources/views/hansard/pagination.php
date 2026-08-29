<?php

/**
 * @file
 * Prev/all/next pagination bar - shown twice, top and bottom of the transcript card
 * (see transcript.php). $edge is 'top' or 'bottom' and decides which side gets the
 * divider line, so both instances get a border between themselves and the card
 * content rather than one floating against the card's own padding edge. $nextPrev
 * has up to three keys - 'prev', 'up', 'next' - each optional, each
 * ['label' => ..., 'url' => ...|null, 'title' => ...]; a present entry with no url is
 * plain text, not a link (matches $PAGE->nextprevlinks()'s own fallback).
 *
 * !-prefixed classes: layout.css has legacy "a:link { color: #00b; text-decoration:
 * underline }" / "a:visited { color: #505; ... }" rules (specificity 0,1,1) that beat
 * a plain Tailwind colour class (0,1,0) regardless of stylesheet order - same fix
 * PR #225's nav bar already uses (see page.php).
 */
?>
<?php
// border-0 border-t/border-b: same preflight-off <hr> issue noted elsewhere in this
// template set - this uses a bordered element instead of a real <hr>, so it needs the
// reset too. p{t,b}-4, not m{t,b}-4: the card's own space-y-8 already puts room around
// this element from its neighbours; padding (inside the border) keeps the divider
// line itself snug against the header/speeches, not floating in the gap.
$borderClass = $edge == 'top' ? 'pb-4 border-0 border-b' : 'pt-4 border-0 border-t';
?>
<nav class="flex items-center justify-between gap-4 <?php echo $borderClass ?> border-slate-200 text-sm" aria-label="Debate navigation">
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
