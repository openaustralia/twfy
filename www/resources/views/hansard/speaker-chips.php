<?php

/**
 * @file
 * A compact, wrapping row of avatar+name chips - "who spoke on this topic", used
 * under one section-index.php entry (eg one MP's topic within an Adjournment
 * debate) and, capped, under one www/docs/index.php "Latest Activity" item (a
 * whole day's Bills debate can run to dozens of speakers). $speakers is a list of
 * HansardSpeakerRosterEntry (see HansardSpeechView::speakerEntry()), in speaking
 * order.
 *
 * Deliberately smaller/plainer than speaker-roster.php's sidebar list (24px avatar,
 * no party/electorate line, no speech count) - this is a scan-at-a-glance byline for
 * a whole topic, not the page's main "who's in this debate" reference. The
 * electorate/office and party line isn't hidden entirely though - it's a native
 * title="" tooltip on hover/focus instead, same $speaker->description
 * speaker-roster.php shows outright.
 *
 * Each chip links to $speaker->firstSpeechUrl when the caller set one (only
 * latest_activity_items() does - where they first speak in *that* item, not their
 * MP/senator profile page, which is $speaker->url, unused here) - section-index.php
 * never sets it, so its own chips stay plain text there, same as before: its linked
 * card variant already wraps the whole chip row in its own <a>, and a nested <a>
 * isn't valid HTML.
 *
 * $moreCount is optional (only the "Latest Activity" caller passes it) - the number
 * of further speakers not in $speakers at all, already capped by the caller. A
 * trailing "+N more" chip, not a link - nowhere specific for it to go to.
 */
?>
<?php
// grid, not flex flex-wrap: names are different lengths, so a flex-wrap row lets
// each chip's own width push the next one sideways - avatars end up at whatever
// x-position that name happened to leave off, different from row to row. A fixed
// two-column grid instead gives every avatar the same column start regardless of
// how long the name next to it is - min-w-0 on the name below is what lets a long
// name actually truncate to that fixed column width instead of stretching it.
?>
<ul class="mt-2 grid grid-cols-2 gap-x-3 gap-y-1.5 list-none">
    <?php foreach ($speakers as $speaker): ?>
        <?php $titleAttr = $speaker->description ? ' title="' . $this->e($speaker->description) . '"' : ''; ?>
        <li class="min-w-0"<?php echo $titleAttr ?>>
            <?php
            // !-prefixed: layout.css's legacy "a:link"/"a:visited" rules
            // (specificity 0,1,1) beat a plain Tailwind colour class (0,1,0)
            // regardless of stylesheet order - see speech.php's own comment on
            // this. Applies here regardless of whether this chip ends up a link or
            // plain text below (a plain <span>/<li> can still inherit a
            // surrounding <a>'s own colour - see section-index.php's linked card
            // variant, still true even without firstSpeechUrl).
            ?>
            <?php
            // aria-label, not title: the <a>'s own title would sit closer in the DOM
            // than the <li>'s title="" above and win the hover tooltip, hiding the
            // party/electorate description this whole component exists to show
            // whenever a chip happens to be a link. aria-label still gives screen
            // readers the link's purpose without competing for the visible tooltip.
            ?>
            <?php if ($speaker->firstSpeechUrl): ?>
                <a href="<?php echo $this->e($speaker->firstSpeechUrl) ?>"
                    class="flex min-w-0 items-center gap-1.5 !no-underline"
                    aria-label="See <?php echo $this->e($speaker->name) ?>'s first speech here">
            <?php else: ?>
                <span class="flex min-w-0 items-center gap-1.5">
            <?php endif; ?>
                <?php if ($speaker->avatarUrl): ?>
                    <img src="<?php echo $this->e($speaker->avatarUrl) ?>" alt=""
                        class="w-6 h-6 rounded-full flex-shrink-0 object-cover object-top">
                <?php else: ?>
                    <span class="w-6 h-6 rounded-full flex-shrink-0 bg-slate-200 text-slate-600 flex items-center justify-center font-semibold text-[10px]">
                        <?php echo $this->e($speaker->initials) ?>
                    </span>
                <?php endif; ?>
                <?php
                // alt="" above: the name text right next to it already says who
                // this is - true whether or not this chip is itself a link.
                ?>
                <span class="truncate text-xs !text-slate-600<?php echo $speaker->firstSpeechUrl ? ' hover:!text-teal-700' : '' ?>"><?php echo $this->e($speaker->name) ?></span>
            <?php if ($speaker->firstSpeechUrl): ?>
                </a>
            <?php else: ?>
                </span>
            <?php endif; ?>
        </li>
    <?php endforeach; ?>
    <?php if (!empty($moreCount)): ?>
        <li class="text-xs font-medium !text-slate-500">+<?php echo (int) $moreCount ?> more</li>
    <?php endif; ?>
</ul>
