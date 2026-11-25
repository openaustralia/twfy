{{-- A part of a profile, which the page can link to by its id. --}}
@props(['id', 'title'])
<section id="{{ $id }}">
<h2 class="!m-0 !mb-4 !block !p-0 text-2xl font-bold !leading-tight !text-slate-900">{{ $title }}</h2>
{{ $slot }}
</section>
