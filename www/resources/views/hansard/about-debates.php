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
<?php
// aria-labelledby, not aria-label: reuses the visible heading text below as this
// landmark's accessible name (axe's landmark-unique rule needs each <aside> on the
// page to be distinguishable - there are two/three here, all otherwise plain
// "complementary" regions with no way to tell them apart by screen reader landmark
// navigation).
?>
<aside class="bg-white rounded-2xl shadow-lg p-6 md:p-8" aria-labelledby="about-debates-heading">
    <?php
    // mt-0 mx-0: layout.css has a bare, unscoped "h2 { margin: 0.2em 14px 10px 18px;
    // color: #B82E00; }" left over from the old design (search "NAT" in that file) -
    // it applies to every <h2> on the site, this one included, and nothing here
    // touches font-size/colour (already overridden by the classes below) so the only
    // parts left to neutralise are the margins. Without mx-0 specifically, the 18px
    // left margin pushed the title out of alignment with the paragraph text below it.
    ?>
    <h2 id="about-debates-heading" class="text-base font-semibold uppercase tracking-wide text-teal-700 mt-0 mb-4 mx-0"><?php echo $this->e($title) ?></h2>
    <div class="text-sm text-slate-600 space-y-3">
        <?php echo $bodyHtml ?>
    </div>
</aside>
