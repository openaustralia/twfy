<?php

/**
 * @file
 * The feature row's first card - "Your Representative" (a resolved MP) or "Track
 * Your Reps" (the postcode form). $block is FrontPageView::mpBlock()'s output.
 *
 * !-prefixed classes: layout.css's legacy "a:link"/"a:visited" rules (specificity
 * 0,1,1) beat a plain Tailwind colour class (0,1,0) regardless of stylesheet order -
 * see resources/views/hansard/speech.php's own comment on this.
 */
?>
<?php if ($block['mode'] === 'known'): ?>
    <h3 class="mb-2 text-lg font-semibold text-slate-900">Your Representative</h3>
    <p class="text-slate-600"><a href="<?php echo $this->e($block['mpUrl']) ?>" class="font-semibold !text-teal-800 hover:!text-teal-600">Find out
            more about <?php echo $this->e($block['mpName']) ?>, your <?php echo $this->e($block['former']) ?> Federal Representative</a>
        (<a href="<?php echo $this->e($block['changeUrl']) ?>" class="!text-teal-800 hover:!text-teal-600">Change</a>)</p>
<?php else: ?>
    <h3 class="mb-2 text-lg font-semibold text-slate-900">Track Your Reps</h3>
    <p class="mb-3 text-slate-600">Find out who your local MP and Senators are, see their speeches, and track
        their activity in Parliament.</p>
    <form action="<?php echo $this->e($block['mpUrl']) ?>" method="get" class="flex justify-center gap-2">
        <label for="pc" class="sr-only">Enter your Australian postcode here</label>
        <input type="text" name="pc" id="pc" size="8" maxlength="10" placeholder="Postcode"
            class="!m-0 w-24 rounded border border-solid border-slate-300 px-3 py-2 text-slate-900">
        <input type="submit" value="Go"
            class="!m-0 rounded bg-teal-700 px-4 py-2 font-semibold text-white hover:bg-teal-600">
    </form>
<?php endif;
