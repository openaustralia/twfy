<?php

/**
 * @file
 * One procedural item (htype 13 - eg "The House divided", a division result, a
 * reading notice) - no speaker, so no avatar/name block, just the body text set a
 * little apart from the surrounding speeches. $item is a HansardProceduralView.
 */
?>
<?php
// border-solid: Tailwind's preflight reset is off project-wide (tailwind.config.js) -
// without it a border-*-width utility on a <div> renders invisible, since preflight
// is what normally sets border-style: solid globally.
?>
<div id="<?php echo $this->e($item->id) ?>" class="border-solid border-t border-b border-slate-200 py-4 text-sm text-slate-600 italic">
    <?php echo $item->bodyHtml /* pre-sanitised hansard body HTML, same pipeline as the old rendering path */ ?>

    <?php if ($item->contextLinkHtml || $item->commentTeaserHtml): ?>
        <?php
        // [&_a]:...: same fix as speech.php's own footer - contextLinkHtml/
        // commentTeaserHtml are bare <a> tags from legacy helpers, overridden to
        // the legacy blue/purple by layout.css's global a:link/a:visited rules
        // without this. Copilot finding on #227.
        //
        // [&_small]:!text-sm [&_strong]:!font-normal + the emoji: same fix as
        // speech.php's own footer, see that file's comment.
        ?>
        <div class="mt-2 flex flex-wrap items-baseline gap-x-3 gap-y-1 not-italic text-slate-500 [&_p]:inline [&_p]:m-0 [&_small]:!text-sm [&_strong]:!font-normal [&_a]:!text-slate-500 [&_a]:hover:!text-teal-700 [&_a]:!no-underline">
            <?php if ($item->contextLinkHtml): ?>
                <?php
                // Wrapped together, not left as two flex siblings - see
                // speech.php's own footer comment on why.
                ?>
                <span><span aria-hidden="true">🔍</span> <?php echo $item->contextLinkHtml ?></span>
            <?php endif; ?>
            <?php echo $item->commentTeaserHtml ?>
        </div>
    <?php endif; ?>
</div>
