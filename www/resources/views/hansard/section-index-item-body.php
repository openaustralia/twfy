<?php

/**
 * @file
 * The excerpt+citation+"who spoke" chips block shared by both card variants in
 * section-index.php (linked <a> and unlinked <div> - a topic with no url yet
 * still has content worth showing). $item is one HansardSectionIndexItem (see
 * HansardSpeechView.php); $firstSpeakerName is $item->speakers[0]->name ?? null,
 * computed once by the caller's foreach rather than recomputed here.
 */
?>
<?php if ($item->excerptHtml || $item->speakers): ?>
    <?php
    // One shared left border/indent for the excerpt+citation and the "who
    // spoke" chips together - they're both about the same thing (what was
    // said here, and by whom), so one continuous left edge reads as a single
    // block. Previously only the excerpt was indented and the chips snapped
    // back out to the title's own margin, which put the avatars visibly out
    // of line with everything just above them.
    ?>
    <div class="mt-2 border-0 border-l-2 border-slate-200 pl-3">
        <?php if ($item->excerptHtml): ?>
            <p class="text-sm italic text-slate-500">
                <?php echo $item->excerptHtml /* already-safe HTML (trim_characters() output, may contain entities like &#8212; - echoed raw same as the old rendering, not re-escaped) */ ?>
                <?php if ($firstSpeakerName): ?>
                    <cite class="mt-1 block not-italic text-xs font-medium text-slate-500">&mdash; <?php echo $this->e($firstSpeakerName) ?></cite>
                <?php endif; ?>
            </p>
        <?php endif; ?>
        <?php if ($item->speakers): ?>
            <p class="<?php echo $item->excerptHtml ? 'mt-3' : '' ?> text-xs font-medium uppercase tracking-wide text-slate-500">Spoke on this topic</p>
            <?php echo $this->fetch('hansard/speaker-chips', ['speakers' => $item->speakers]) ?>
        <?php endif; ?>
    </div>
<?php endif;
