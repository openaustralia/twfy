<?php

/**
 * @file
 * One "Latest Activity" column (House or Senate). $items is a list of
 * ['title' => already-safe HTML, 'speaker' => plain text or '', 'when' => plain
 * text, 'url' => plain URL] - built by www/docs/index.php's own
 * latest_activity_items(), which is DB-coupled (two queries per item) so stays
 * there rather than moving into FrontPageView.php alongside the DB-free logic.
 */
?>
<div>
    <?php
    // Heroicons "building-library" (MIT) - same icon the debate transcript page uses
    // next to the chamber name (see resources/views/hansard/transcript.php).
    ?>
    <h3 class="mb-3 flex items-center gap-2 text-lg font-semibold text-slate-900">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
            stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
            class="h-5 w-5 <?php echo $this->e($iconColorClass) ?>" aria-hidden="true">
            <path d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0 0 12 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75Z" />
        </svg>
        <?php echo $this->e($chamberName) ?>
    </h3>
    <div class="space-y-3">
        <?php foreach ($items as $item): ?>
            <a href="<?php echo $this->e($item['url']) ?>"
                class="block rounded-lg bg-slate-50 p-4 !no-underline hover:bg-slate-100">
                <p class="font-semibold !text-slate-900"><?php echo $item['title'] /* already-safe HTML, same source as the old rendering */ ?></p>
                <?php if ($item['speaker']): ?>
                    <p class="text-sm text-slate-600">Spoken by: <?php echo $this->e($item['speaker']) ?></p>
                <?php endif; ?>
                <?php
                // text-slate-600, not slate-400: 2.45:1 on the card's slate-50
                // background (axe-core color-contrast, needs 4.5:1).
                ?>
                <p class="text-xs text-slate-600"><?php echo $this->e($item['when']) ?></p>
            </a>
        <?php endforeach; ?>
    </div>
    <a href="<?php echo $this->e($dayUrl) ?>" class="mt-4 inline-block font-semibold !text-teal-800 hover:!text-teal-600 !no-underline">View
        all from <?php echo $this->e($viewAllLabel) ?> &rarr;</a>
</div>
