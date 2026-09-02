<?php

/**
 * @file
 * "Speakers in this debate" sidebar, from the mockup's own roster block. $speakers
 * is a list of HansardSpeakerRosterEntry (see HansardSpeechView::buildRoster()),
 * already sorted most-said-first - nothing here decides ordering or counts.
 */
?>
<?php
// aria-labelledby: see about-debates.php's own comment - makes this landmark
// distinguishable from the page's other <aside> regions for screen reader landmark
// navigation, reusing the visible heading text rather than duplicating it.
?>
<aside class="bg-white rounded-2xl shadow-lg p-6 md:p-8" aria-labelledby="speaker-roster-heading">
    <?php
    // mt-0 mx-0: same unscoped legacy "h2 { margin: 0.2em 14px 10px 18px; }" rule
    // (layout.css, search "NAT") that about-debates.php's own h2 has to neutralise -
    // see that file's comment for the full explanation.
    ?>
    <h2 id="speaker-roster-heading" class="text-base font-semibold uppercase tracking-wide text-teal-700 mb-4 mt-0 mx-0">Speakers in this Debate</h2>
    <ul class="space-y-3">
        <?php
        // Only the photo and the name link to the speaker's profile - the party/
        // electorate line and the speech count are facts about them, not navigation,
        // so they sit outside either link as plain text.
        //
        // items-start, not items-center: most speakers have a description line under
        // their name, so the text block is taller than the avatar - centering the row
        // put the avatar's middle level with the gap between the two lines instead of
        // with the name itself.
        ?>
        <?php foreach ($speakers as $speaker): ?>
            <li class="flex items-start gap-3">
                <?php if ($speaker->avatarUrl): ?>
                    <?php $avatar = '<img src="' . $this->e($speaker->avatarUrl) . '" alt="Photo of ' . $this->e($speaker->name) . '" class="w-8 h-8 rounded-full flex-shrink-0 object-cover object-top">' ?>
                <?php else: ?>
                    <?php
                    // initials() (HansardSpeechView.php) can return '' - a chair role
                    // like "The Deputy Speaker" can lack both first_name and last_name -
                    // which would otherwise render this circle with nothing in it. Fall
                    // back to the first letter of the resolved name instead.
                    $fallbackLetter = $speaker->initials !== '' ? $speaker->initials : mb_strtoupper(mb_substr(trim($speaker->name), 0, 1));
                    ?>
                    <?php $avatar = '<div class="w-8 h-8 rounded-full flex-shrink-0 bg-slate-200 text-slate-600 flex items-center justify-center font-semibold text-xs">' . $this->e($fallbackLetter) . '</div>' ?>
                <?php endif; ?>
                <?php if ($speaker->url): ?>
                    <a href="<?php echo $this->e($speaker->url) ?>" class="!no-underline" title="See more information about <?php echo $this->e($speaker->name) ?>">
                        <?php echo $avatar ?>
                    </a>
                <?php else: ?>
                    <?php echo $avatar ?>
                <?php endif; ?>
                <div class="min-w-0 flex-grow">
                    <?php if ($speaker->url): ?>
                        <?php
                        // !-prefixed: layout.css's legacy "a:link"/"a:visited" rules
                        // (specificity 0,1,1) beat a plain Tailwind colour class
                        // (0,1,0) regardless of stylesheet order - see speech.php's
                        // own comment on this.
                        ?>
                        <a href="<?php echo $this->e($speaker->url) ?>" class="block text-sm font-semibold !text-slate-900 hover:!text-teal-700 !no-underline truncate"
                            title="See more information about <?php echo $this->e($speaker->name) ?>"><?php echo $this->e($speaker->name) ?></a>
                    <?php else: ?>
                        <p class="text-sm font-semibold text-slate-900 truncate"><?php echo $this->e($speaker->name) ?></p>
                    <?php endif; ?>
                    <?php if ($speaker->description): ?>
                        <p class="text-sm text-slate-500 truncate"><?php echo $speaker->description /* already-escaped by HansardSpeechView */ ?></p>
                    <?php endif; ?>
                </div>
                <p class="text-xs text-slate-500 flex-shrink-0"
                    title="<?php echo $this->e($speaker->name) ?> spoke <?php echo $speaker->speechCount ?> time<?php echo $speaker->speechCount == 1 ? '' : 's' ?>">
                    <?php echo $speaker->speechCount ?>x
                </p>
            </li>
        <?php endforeach; ?>
    </ul>
</aside>
