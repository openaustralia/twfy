{{-- A box beside a page. --}}
@props(['title'])
<section class="rounded-lg bg-slate-100 p-4">
<h2 class="!m-0 !mb-2 !block !p-0 text-base font-semibold !text-slate-900">{{ $title }}</h2>
{{ $slot }}
</section>
