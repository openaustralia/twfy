{{-- A part of a profile, which the page can link to by its id. --}}
@props(['id', 'title'])
<section id="{{ $id }}" class="mt-8">
<h2 class="!m-0 !mb-3 !block !p-0 text-xl font-semibold !text-slate-900">{{ $title }}</h2>
{{ $slot }}
</section>
