<?php

/**
 * @file
 * The mockup's 3-icon feature row ("Track Your Reps" / "Read the Debates" / "Get
 * Free Email Alerts") - same three things the old "At OpenAustralia.org you can:"
 * bullet list offered. $mpBlock/$emailAlertText are FrontPageView::mpBlock()/
 * emailAlertText()'s output; $hansardUrl/$emailAlertUrl are plain URLs (the mp-card's
 * own URLs already live inside $mpBlock).
 */

$featureIconView = 'front/feature-icon';
?>
<section class="mx-4 mb-12 rounded-2xl bg-slate-50 px-4 py-10 md:mx-8 md:px-8">
    <div class="mx-auto grid max-w-5xl grid-cols-1 gap-6 md:grid-cols-3">
        <div class="rounded-2xl bg-white p-6 text-center shadow-md md:p-8">
            <?php echo $this->fetch($featureIconView, ['emoji' => '👤']) ?>
            <?php echo $this->fetch('front/mp-card', ['block' => $mpBlock]) ?>
        </div>
        <?php
        // The whole card links to the same place as the "Debates" nav item
        // (metadata.php's 'hansard' page) - !-prefixed classes since a plain colour
        // class loses to layout.css's legacy "a:link" rule (specificity 0,1,1 beats a
        // class selector's 0,1,0), same fix used throughout the debate transcript
        // page's own templates this session.
        ?>
        <a href="<?php echo $this->e($hansardUrl) ?>"
            class="block rounded-2xl bg-white p-6 text-center shadow-md !text-inherit !no-underline hover:shadow-lg md:p-8">
            <?php echo $this->fetch($featureIconView, ['emoji' => '📜']) ?>
            <h3 class="mb-2 text-lg font-semibold text-slate-900">Read the Debates</h3>
            <p class="text-slate-600">Access and search the complete record of what's said in the House of
                Representatives and the Senate.</p>
        </a>
        <a href="<?php echo $this->e($emailAlertUrl) ?>"
            class="block rounded-2xl bg-white p-6 text-center shadow-md !text-inherit !no-underline hover:shadow-lg md:p-8">
            <?php echo $this->fetch($featureIconView, ['emoji' => '✉️']) ?>
            <?php echo $this->fetch('front/email-card', ['text' => $emailAlertText]) ?>
        </a>
    </div>
</section>
