<?php

/**
 * @file
 * Prev/all/next pagination bar - shown twice, top and bottom of the transcript card
 * (see transcript.php). $edge is 'top' or 'bottom' and decides which side gets the
 * divider line, so both instances get a border between themselves and the card
 * content rather than one floating against the card's own padding edge. $nextPrev
 * has up to three keys - 'prev', 'up', 'next' - each optional, each
 * ['label' => ..., 'url' => ...|null, 'title' => ...]; a present entry with no url is
 * shown as an unclickable card, not a link (matches $PAGE->nextprevlinks()'s own
 * fallback). Prev/next are card buttons - 'label' ("Previous debate"/"Next debate")
 * as a small eyebrow, 'title' (the neighbouring debate's actual name, eg "Motions") as
 * the visible headline - it's the useful part, and used to only surface as a hover
 * tooltip. 'up' stays a plain centred link; it's a "see everything", not a single
 * specific destination the way prev/next are.
 *
 * !-prefixed classes: layout.css has legacy "a:link { color: #00b; text-decoration:
 * underline }" / "a:visited { color: #505; ... }" rules (specificity 0,1,1) that beat
 * a plain Tailwind colour class (0,1,0) regardless of stylesheet order - same fix
 * PR #225's nav bar already uses (see page.php).
 */
?>
<?php
// border-0 border-t/border-b, and border-solid: Tailwind's preflight reset is off
// project-wide (tailwind.config.js) - preflight is what normally sets
// border-style: solid globally, so a border-*-width utility renders invisible
// without it (the browser's own default border-style on a <div> is "none", not
// "solid" the way it is on eg <hr>/<table>). Same root cause as the header <hr>
// elsewhere in this template set. The prev/next cards below dropped their own border
// (too busy alongside this divider and the card's own outer border) - just a
// hover:bg-slate-50 now - so this divider is the only border left in this file.
// p{t,b}-4, not m{t,b}-4: the card's own space-y-8 already puts room around this
// element from its neighbours; padding (inside the border) keeps the divider line
// itself snug against the header/speeches, not floating in the gap.
$borderClass = $edge == 'top' ? 'pb-4 border-0 border-solid border-b' : 'pt-4 border-0 border-solid border-t';
?>
<nav class="flex items-stretch justify-between gap-3 <?php echo $borderClass ?> border-slate-200" aria-label="Debate navigation">
    <div class="flex-1 min-w-0">
        <?php if (isset($nextPrev['prev'])): ?>
            <?php if ($nextPrev['prev']['url']): ?>
                <a href="<?php echo $this->e($nextPrev['prev']['url']) ?>"
                    class="group block rounded-lg px-4 py-2.5 !no-underline hover:bg-slate-50 transition-colors">
                    <span class="block text-xs font-semibold uppercase tracking-wide text-slate-400 group-hover:text-teal-700">⬅️ <?php echo $this->e($nextPrev['prev']['label']) ?></span>
                    <?php if ($nextPrev['prev']['title']): ?>
                        <span class="block text-sm font-medium !text-slate-900 truncate"><?php echo $this->e($nextPrev['prev']['title']) ?></span>
                    <?php endif; ?>
                </a>
            <?php else: ?>
                <div class="block rounded-lg px-4 py-2.5">
                    <span class="block text-xs font-semibold uppercase tracking-wide text-slate-300">⬅️ <?php echo $this->e($nextPrev['prev']['label']) ?></span>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <div class="flex-shrink-0 flex items-center justify-center px-2 text-sm">
        <?php if (isset($nextPrev['up'])): ?>
            <?php if ($nextPrev['up']['url']): ?>
                <a href="<?php echo $this->e($nextPrev['up']['url']) ?>" class="!text-slate-700 hover:!text-teal-700 !no-underline whitespace-nowrap"
                    title="<?php echo $this->e($nextPrev['up']['title']) ?>"><?php echo $this->e($nextPrev['up']['label']) ?></a>
            <?php else: ?>
                <span class="text-slate-700 whitespace-nowrap"><?php echo $this->e($nextPrev['up']['label']) ?></span>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <div class="flex-1 min-w-0">
        <?php if (isset($nextPrev['next'])): ?>
            <?php if ($nextPrev['next']['url']): ?>
                <a href="<?php echo $this->e($nextPrev['next']['url']) ?>"
                    class="group block rounded-lg px-4 py-2.5 text-right !no-underline hover:bg-slate-50 transition-colors">
                    <span class="block text-xs font-semibold uppercase tracking-wide text-slate-400 group-hover:text-teal-700"><?php echo $this->e($nextPrev['next']['label']) ?> ➡️</span>
                    <?php if ($nextPrev['next']['title']): ?>
                        <span class="block text-sm font-medium !text-slate-900 truncate"><?php echo $this->e($nextPrev['next']['title']) ?></span>
                    <?php endif; ?>
                </a>
            <?php else: ?>
                <div class="block rounded-lg px-4 py-2.5 text-right">
                    <span class="block text-xs font-semibold uppercase tracking-wide text-slate-300"><?php echo $this->e($nextPrev['next']['label']) ?> ➡️</span>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</nav>
