<?php

/**
 * @file
 * A "Home / House of Representatives / 20 August 2026 / Adjournment" trail, currently
 * used on section-index pages only (see hansard_gid.php's $data['subrows']
 * handling) - the transcript page (transcript.php) doesn't have one yet.
 *
 * $crumbs is a list of ['label' => string, 'url' => ?string], in order, Home first.
 * A crumb with no 'url' (or an empty one) renders as plain text, not a link - meant
 * for the last, current-page crumb, but nothing here assumes it's only ever the last
 * one.
 */
?>
<nav aria-label="Breadcrumb" class="mb-3 md:mb-4 px-1">
    <ol class="flex flex-wrap items-center gap-x-1.5 gap-y-1 text-sm text-slate-500">
        <?php foreach ($crumbs as $n => $crumb): ?>
            <?php if ($n > 0): ?>
                <li aria-hidden="true" class="text-slate-400">/</li>
            <?php endif; ?>
            <li>
                <?php if (!empty($crumb['url']) && $n < count($crumbs) - 1): ?>
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
                    // one you're already on.
                    ?>
                    <span class="font-medium text-slate-700" aria-current="page"><?php echo $this->e($crumb['label']) ?></span>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ol>
</nav>
