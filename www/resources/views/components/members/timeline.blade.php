{{-- One history on a profile's dark card. --}}
@use('OpenAustralia\TWFY\Http\HouseTheme')
@props(['title', 'entries', 'label'])
<div>
<h2 class="!m-0 !mb-3.5 !block !p-0 text-sm font-semibold uppercase tracking-[0.06em] {{ $label }}">{{ $title }}</h2>
<ol class="!m-0 !ml-1.5 list-none !p-0">
@foreach ($entries as $entry)
@php($dot = $entry['gap'] ? 'border-dashed bg-transparent' : 'border-solid ' . ($entry['house'] ? HouseTheme::for($entry['house'])->chip : 'bg-slate-300'))
<li class="relative !m-0 border-0 border-l-2 {{ $entry['gap'] ? 'border-dashed' : 'border-solid' }} {{ $loop->last ? 'border-transparent' : 'border-white/25' }} pb-4 pl-6">
<span aria-hidden="true" class="absolute -left-[9px] top-[3px] box-border h-4 w-4 rounded-full border-2 border-white {{ $dot }}"></span>
<div class="text-[17px] font-semibold {{ $entry['gap'] ? 'italic !text-slate-300' : '!text-white' }}">{{ $entry['name'] }}
@if ($entry['current'])
<span class="ml-2 inline-block rounded-full border border-solid border-current px-2 py-px align-middle text-xs font-semibold {{ $label }}">Current</span>
@endif
</div>
<div class="text-sm !text-slate-300">{{ $entry['dates'] }}</div>
@if ($entry['detail'])
<div class="text-sm !text-slate-300">{{ $entry['detail'] }}</div>
@endif
</li>
@endforeach
</ol>
</div>
