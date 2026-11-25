{{-- Offices someone used to hold, tagged with a house when the title names one. --}}
@use('OpenAustralia\TWFY\Http\HouseTheme')
@props(['offices'])
<ul class="!m-0 list-none !p-0">
@foreach ($offices as $office)
<li class="!m-0 border-0 border-t border-solid border-slate-200 py-3">
<div class="text-base font-semibold !text-slate-900">{{ $office['title'] }}
@if ($office['house'])
<span class="ml-1 inline-block rounded-full {{ HouseTheme::for($office['house'])->chip }} px-2 py-px align-middle text-xs font-bold !text-white">{{ $office['house'] === \OpenAustralia\TWFY\Enums\House::Senate ? 'Senate' : 'House' }}</span>
@endif
</div>
<div class="text-sm !text-slate-600">{{ $office['dates'] }}</div>
</li>
@endforeach
</ul>
