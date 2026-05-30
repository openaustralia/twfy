<?php

/**
 * @file
 */
?>
<div class="mx-auto max-w-4xl">
    <section class="rounded-2xl border border-[#e4d5c7] bg-gradient-to-br from-white to-[#fdf5f5] p-6 shadow-sm md:p-8">
        <h2 class="mb-4 text-3xl font-extrabold tracking-tight text-heading">Contact the OpenAustralia team</h2>

        <p class="text-[17px] leading-8 text-slate-800">
            Please tell us what you think about <strong>OpenAustralia.org</strong>
        </p>

        <ul class="mt-5 list-disc space-y-1.5 pl-6 text-[17px] leading-8 text-slate-800 marker:text-brand">
            <li>Did it work?</li>
            <li>Do you like it? </li>
            <li>How can we improve it? </li>
        </ul>

        <p class="mt-5 text-[17px] leading-8 text-slate-800">
            We seek responses to all these questions.
        </p>

        <p class="mt-6 rounded-xl border border-accent/50 bg-[#fff3e8] px-4 py-3 text-[16px] leading-7 text-[#5d2d20]">
            The email address of OpenAustralia.org, which is run by a <strong>charity</strong>, is: <a
                class="font-semibold text-brand underline decoration-accent/70 underline-offset-2 hover:text-heading"
                href="mailto:<?php echo str_replace('@', '&#64;', CONTACTEMAIL); ?>"><?php echo str_replace('@', '&#64;', CONTACTEMAIL); ?></a>
        </p>
    </section>
</div>
