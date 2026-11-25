{{-- A box beside a profile: bordered, or with $soft a pale fill. --}}
@props(['title', 'soft' => false])
<section class="{{ $soft ? 'rounded-2xl bg-slate-50' : 'rounded-lg border border-solid border-slate-300 bg-white' }} p-5">
<h2 class="!m-0 !mb-2 !block !p-0 text-lg font-bold !leading-snug !text-slate-900">{{ $title }}</h2>
{{ $slot }}
</section>
