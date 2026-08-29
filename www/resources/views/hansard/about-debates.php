<?php

/**
 * @file
 * "What are House debates?"/"What are Senate debates?" - was a sidebar block that
 * only linked out to /debates/#help or /senate/#help (see sidebars/hocdebates_short.php
 * and holdebates_short.php); the actual explanation is inlined here instead, in a card
 * of its own. $title and $bodyHtml are already-safe HTML, built in hansard_gid.php from
 * the same wording as sidebars/hocdebates.php / holdebates.php.
 */
?>
<aside class="bg-white rounded-2xl shadow-lg p-6 md:p-8">
    <?php
    // mt-0: Tailwind's preflight reset is off project-wide (tailwind.config.js), and
    // global.css only sets h2's font-size, not its margin - so without this, the
    // browser's own default h2 top-margin stacks above mb-4's bottom-margin, pushing
    // this title (and the body text under it) out of line with the roster card's own
    // "Speakers in this Debate" title below.
    ?>
    <h2 class="text-base font-semibold uppercase tracking-wide text-teal-700 mt-0 mb-4"><?php echo $this->e($title) ?></h2>
    <div class="text-sm text-slate-600 space-y-3">
        <?php echo $bodyHtml ?>
    </div>
</aside>
