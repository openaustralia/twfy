{{-- A part of a profile, which the page can link to by its id. $aside sits on the heading's right. --}}
@props(['id', 'title', 'aside' => null])
<section id="{{ $id }}">
<div class="mb-4 flex flex-wrap items-center justify-between gap-x-4 gap-y-1">
<h2 class="!m-0 !block !p-0 text-2xl font-bold !leading-tight !text-slate-900">{{ $title }}</h2>
@if ($aside)
<div class="ml-auto">{{ $aside }}</div>
@endif
</div>
{{ $slot }}
</section>
