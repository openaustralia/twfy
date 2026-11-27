{{-- The charity's appeal, tinted for a house. --}}
@props(['theme'])
<section class="rounded-2xl {{ $theme->tint }} p-5">
<h2 class="!m-0 !mb-2 !block !p-0 text-lg font-bold !leading-snug !text-slate-900">Did you know this site is run by a charity?</h2>
<p class="!m-0 !mb-4 text-[15px] !text-slate-900">Your donation would enable us to continue to add new features.</p>
<a href="https://donate.oaf.org.au/" class="box-border inline-flex min-h-[44px] items-center gap-2 rounded {{ $theme->button }} px-7 py-2.5 text-base font-semibold !text-white !no-underline shadow">
<x-icon path="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8l1 1.1L12 21.2l7.8-7.7 1-1.1a5.5 5.5 0 0 0 0-7.8z" />
Donate
</a>
</section>
