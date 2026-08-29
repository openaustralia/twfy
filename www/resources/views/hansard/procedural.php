<?php

/**
 * @file
 * One procedural item (htype 13 - eg "The House divided", a division result, a
 * reading notice) - no speaker, so no avatar/name block, just the body text set a
 * little apart from the surrounding speeches. $item is a HansardProceduralView.
 */
?>
<div id="<?php echo $this->e($item->id) ?>" class="border-t border-b border-slate-200 py-4 text-sm text-slate-600 italic">
    <?php echo $item->bodyHtml /* pre-sanitised hansard body HTML, same pipeline as the old rendering path */ ?>

    <?php if ($item->contextLinkHtml || $item->commentTeaserHtml): ?>
        <div class="mt-2 not-italic text-slate-500 space-x-3">
            <?php echo $item->contextLinkHtml ?>
            <?php echo $item->commentTeaserHtml ?>
        </div>
    <?php endif; ?>
</div>
