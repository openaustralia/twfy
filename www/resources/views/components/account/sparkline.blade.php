{{-- A small line of how often someone spoke each month, with the total beside it. $chart comes from MonthlyChart. --}}
@props(['chart'])
<div class="flex flex-[0_1_auto] items-center gap-2.5">
<svg width="132" height="36" viewBox="0 0 132 36" role="img" aria-label="{{ $chart['label'] }}" class="block overflow-visible text-teal-700">
<line x1="0" y1="33" x2="132" y2="33" stroke="#e2e8f0" stroke-width="1"></line>
<path d="{{ $chart['path'] }}" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
<circle cx="{{ $chart['lastX'] }}" cy="{{ $chart['lastY'] }}" r="4" fill="currentColor" stroke="#ffffff" stroke-width="2"></circle>
@foreach ($chart['points'] as $point)
<rect x="{{ $point['left'] }}" y="0" width="11" height="36" fill="transparent"><title>{{ $point['label'] }}</title></rect>
@endforeach
</svg>
<p class="!m-0 w-[88px] text-[13px] leading-[1.3] text-slate-700"><strong class="text-base text-slate-900">{{ number_format($chart['total']) }}</strong> {{ $chart['unit'] }} in 12 months</p>
</div>
