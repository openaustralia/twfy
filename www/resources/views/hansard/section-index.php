<?php

/**
 * @file
 * A section-index page's list of topics - eg an Adjournment debate's own gid page,
 * where each MP's separate, unrelated topic ("Climate Change", "Humanitarian and
 * Refugee Visas", ...) links out to its own already-Plates-rendered transcript page,
 * rather than showing full speeches inline here (see hansard_gid.php's
 * $data['subrows'] handling). $items is a list of HansardSectionIndexItem (see
 * HansardSpeechView.php).
 */
?>
<ul class="list-none space-y-3">
    <?php foreach ($items as $item): ?>
        <li>
            <?php
            // !-prefixed classes: layout.css's legacy "a:link"/"a:visited" rules
            // (specificity 0,1,1) beat a plain Tailwind colour class (0,1,0)
            // regardless of stylesheet order - see speech.php's own comment on this.
            ?>
            <?php if ($item->url): ?>
                <a href="<?php echo $this->e($item->url) ?>"
                    class="block rounded-lg bg-slate-50 p-4 !no-underline hover:bg-slate-100">
                    <p class="font-semibold !text-slate-900"><?php echo $item->titleHtml /* already-safe HTML, same source as the old stripe rendering */ ?></p>
                    <?php if ($item->countLabel): ?>
                        <p class="text-sm text-slate-600"><?php echo $this->e($item->countLabel) ?></p>
                    <?php endif; ?>
                    <?php if ($item->excerptHtml): ?>
                        <p class="mt-1 text-sm text-slate-600"><?php echo $item->excerptHtml /* already-safe HTML (trim_characters() output, may contain entities like &#8212; - echoed raw same as the old rendering, not re-escaped) */ ?></p>
                    <?php endif; ?>
                    <?php if ($item->speakers): ?>
                        <?php echo $this->fetch('hansard/speaker-chips', ['speakers' => $item->speakers]) ?>
                    <?php endif; ?>
                </a>
            <?php else: ?>
                <div class="rounded-lg bg-slate-50 p-4">
                    <p class="font-semibold text-slate-900"><?php echo $item->titleHtml /* already-safe HTML, same source as the old stripe rendering */ ?></p>
                    <?php if ($item->excerptHtml): ?>
                        <p class="mt-1 text-sm text-slate-600"><?php echo $item->excerptHtml /* already-safe HTML - see the <a> branch's comment above */ ?></p>
                    <?php endif; ?>
                    <?php if ($item->speakers): ?>
                        <?php echo $this->fetch('hansard/speaker-chips', ['speakers' => $item->speakers]) ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </li>
    <?php endforeach; ?>
</ul>
