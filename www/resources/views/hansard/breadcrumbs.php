<?php

/**
 * @file
 * A "Home / House of Representatives / 20 August 2026 / Adjournment" trail, used on
 * both the section-index page (section-index-page.php) and the transcript page
 * (transcript.php).
 *
 * $crumbs is a list of ['label' => string, 'url' => ?string], in order, Home first.
 * A crumb with no 'url' (or an empty one) renders as plain text, not a link - meant
 * for the last, current-page crumb, but nothing here assumes it's only ever the last
 * one.
 */
?>
<?php
// Separators are CSS content (after:content-['›']), not literal "/" <li> elements of
// their own - the WAI-ARIA breadcrumb pattern's own recommendation. With a separator
// as its own list item, a screen reader announces eg "list, 7 items" for what's
// really only 4 crumbs (three "/"s counted right along with them) - purely visual
// punctuation has no business being counted as content.
?>
<nav aria-label="Breadcrumb" class="mb-3 md:mb-4 px-1">
    <ol class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-slate-500">
        <?php foreach ($crumbs as $n => $crumb): ?>
            <?php
            $isLast = $n == count($crumbs) - 1;
            // font-normal: without it, the separator would inherit the current
            // page's own font-medium (below) when it's the second-to-last crumb.
            $liClass = 'flex items-center gap-2'
                . ($isLast ? '' : " after:content-['›'] after:font-normal after:text-slate-400");
            ?>
            <li class="<?php echo $liClass ?>">
                <?php if (!empty($crumb['url']) && !$isLast): ?>
                    <?php
                    // !-prefixed: layout.css's legacy "a:link"/"a:visited" rules
                    // (specificity 0,1,1) beat a plain Tailwind colour class (0,1,0)
                    // regardless of stylesheet order - see speech.php's own comment
                    // on this.
                    ?>
                    <a href="<?php echo $this->e($crumb['url']) ?>" class="!text-slate-500 hover:!text-teal-700 !no-underline"><?php echo $this->e($crumb['label']) ?></a>
                <?php else: ?>
                    <?php
                    // The current page's own crumb - never a link even if it has a
                    // url, and marked aria-current so screen readers know it's the
                    // one you're already on. truncate + title="": the one crumb
                    // genuinely likely to be long (a bill's full name, eg "Social
                    // Security and Other Legislation Amendment (Technical Changes
                    // No. 2) Bill 2026; Second Reading") shouldn't be able to blow
                    // out the whole trail's width on a narrow screen - the full text
                    // is still there on hover/focus.
                    ?>
                    <span class="block max-w-[70vw] truncate font-medium text-slate-700 sm:max-w-xs" aria-current="page" title="<?php echo $this->e($crumb['label']) ?>"><?php echo $this->e($crumb['label']) ?></span>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ol>
</nav>
