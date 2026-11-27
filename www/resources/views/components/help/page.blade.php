{{-- A help page: the page heading, then its words in a white card on the pale band. --}}
@props(['title'])
<x-page>
<h1 class="!m-0 !mb-6 !block !p-0 text-[clamp(28px,4vw,36px)] font-bold !leading-tight text-slate-900">{{ $title }}</h1>
<x-card class="max-w-3xl">
<x-prose>{{ $slot }}</x-prose>
</x-card>
</x-page>
