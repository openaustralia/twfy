<?php

/**
 * @file
 * One speech, in the flow built by transcript.php. $speech is a HansardSpeechView
 * (see HansardSpeechView.php) - already-finished data, no raw hansard row fields here.
 */
?>
<div id="<?php echo $this->e($speech->id) ?>"
    class="flex items-start gap-5<?php
        if ($speech->isCurrentSpeaker) {
            echo ' border-l-4 border-teal-700 pl-4 -ml-4';
        } elseif ($speech->isInterjection) {
            echo ' border-l-4 border-amber-400 bg-amber-50/60 rounded-r-lg pl-4 py-2 -ml-4';
        }
    ?>">
    <?php if ($speech->avatarUrl): ?>
        <img src="<?php echo $this->e($speech->avatarUrl) ?>"
            alt="Photo of <?php echo $this->e($speech->speakerName ?? '') ?>"
            class="w-16 h-16 rounded-full flex-shrink-0 object-cover object-top">
    <?php elseif ($speech->speakerName): ?>
        <div class="w-16 h-16 rounded-full flex-shrink-0 bg-slate-200 text-slate-600 flex items-center justify-center font-semibold text-lg">
            <?php echo $this->e(mb_strtoupper(mb_substr($speech->speakerName, 0, 1))) ?>
        </div>
    <?php endif; ?>

    <div class="flex-grow min-w-0">
        <?php if ($speech->speakerName): ?>
            <?php
            // Plain inline flow, not flex+gap: these are two <span>s with only
            // whitespace between them in the source, and flex's `gap` only spaces out
            // real flex items - a whitespace-only text node isn't one, so nothing
            // rendered between them. Inline flow renders that same whitespace as an
            // ordinary space, and still wraps naturally on narrow screens.
            ?>
            <p class="mb-3">
                <span class="font-bold text-slate-900">
                    <?php
                    // ! prefixes throughout this file's links: layout.css has legacy
                    // "a:link { color: #00b; text-decoration: underline }" /
                    // "a:visited { color: #505; ... }" rules (specificity 0,1,1) that
                    // beat a plain Tailwind colour class (0,1,0) regardless of stylesheet
                    // order - same fix PR #225's nav bar already uses (see page.php).
                    // Without it, every link here rendered classic underlined blue.
                    ?>
                    <?php if ($speech->speakerUrl): ?>
                        <a href="<?php echo $this->e($speech->speakerUrl) ?>" class="!text-slate-900 hover:!text-teal-700 !no-underline"
                            title="See more information about <?php echo $this->e($speech->speakerName) ?>"><?php echo $this->e($speech->speakerName) ?></a>
                    <?php else: ?>
                        <?php echo $this->e($speech->speakerName) ?>
                    <?php endif; ?>
                </span>
                <?php
                // text-slate-500 matches the mockup's own text-gray-500 for this line
                // (kept at the same font size as the name, per earlier feedback -
                // the mockup itself uses text-sm here, smaller than the name).
                ?>
                <?php if ($speech->speakerDescription): ?>
                    <span class="text-slate-500"><?php echo $speech->speakerDescription /* already-escaped by HansardSpeechView */ ?></span>
                <?php endif; ?>
            </p>
        <?php endif; ?>

        <?php if ($speech->timestamp): ?>
            <p class="text-xs text-slate-500 font-medium uppercase tracking-wide mb-2"><?php echo $this->e($speech->timestamp) ?></p>
        <?php endif; ?>

        <?php
        // Production marks formal motion wording in olive green (div.main p.italic,
        // p.indentitalic, p.moved all get color: #4D6C25 in layout.css) - .moved is the
        // "I move that..." header line HansardSpeechView::cleanBody() restores from the
        // parser's pwmotiontext attribute, .italic/.indentitalic are the motion's own
        // quoted text. Those old rules key off div.main, which this new card never wraps
        // itself in, so without this they render as plain, unmarked text. Reusing the
        // idea (green = "this is a motion", distinct from teal/amber) with an emerald
        // that fits the new palette rather than the old rule's literal hex.
        ?>
        <?php
        // font-size/line-height match the mockup's own `prose` block exactly (Tailwind
        // Typography's default: 1rem/1.75) rather than the text-lg/leading-relaxed guess
        // used before there was a real value to match against.
        ?>
        <div class="text-base leading-[1.75] [&_p]:mb-4 [&_p:last-child]:mb-0
            [&_.moved]:font-semibold [&_.moved]:text-emerald-700
            [&_.italic]:italic [&_.italic]:text-emerald-700
            [&_.indentitalic]:italic [&_.indentitalic]:text-emerald-700 [&_.indentitalic]:ml-[4em]<?php
            echo $speech->isInterjection
                ? ' italic text-slate-500 [&_.unknownspeaker]:not-italic [&_.unknownspeaker]:font-semibold [&_.unknownspeaker]:text-slate-600'
                : ' text-slate-800';
        ?>">
            <?php echo $speech->bodyHtml /* pre-sanitised hansard body HTML, same pipeline as the old rendering path */ ?>
        </div>

        <?php if ($speech->sourceUrl || $speech->contextLinkHtml || $speech->commentTeaserHtml): ?>
            <div class="mt-3 text-sm text-slate-500 space-x-3">
                <?php if ($speech->sourceUrl): ?>
                    <a href="<?php echo $this->e($speech->sourceUrl) ?>" class="!text-slate-500 hover:!text-teal-700 !no-underline"
                        title="The source of this piece of text"><?php echo $this->e($speech->sourceLabel) ?></a>
                <?php endif; ?>
                <?php echo $speech->contextLinkHtml ?>
                <?php echo $speech->commentTeaserHtml ?>
            </div>
        <?php endif; ?>
    </div>
</div>
