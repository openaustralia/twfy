{{-- A line of contributions per year. Hovering a year shows its count. --}}
@use('Illuminate\Support\Str')
@if ($speaking)
@php
$first = array_key_first($speaking);
$last = array_key_last($speaking);
$step = 232 / (count($speaking) - 1);
$points = [];
foreach ($speaking as $year => $count) {
    $points[$year] = [
        'x' => round(4 + count($points) * $step, 1),
        'y' => round(50 - $count / max($speaking) * 34, 1),
        'text' => $year . ': ' . number_format($count) . ' ' . Str::plural('contribution', $count) . ($year === now()->year ? ' so far' : ''),
    ];
}
$line = 'M' . implode(' L', array_map(fn (array $point): string => $point['x'] . ' ' . $point['y'], $points));
// The peak and the latest year are labelled, once if they're the same.
$peak = array_search(max($speaking), $speaking, true);
@endphp
<section aria-labelledby="speaking" class="flex flex-wrap items-center gap-x-6 gap-y-2 rounded-lg border border-solid border-slate-300 bg-white px-4 py-3.5">
<div class="min-w-0 flex-[1_1_200px]">
<h2 id="speaking" class="!m-0 !block !p-0 text-base font-bold !text-slate-900">Speaking in Hansard</h2>
<p class="!m-0 text-sm !text-slate-700">Contributions per year, {{ $first }} to {{ $last }}</p>
</div>
<div class="flex-none">
<svg width="240" height="56" viewBox="0 0 240 56" role="img" aria-label="Line chart of {{ $profile->name() }}'s Hansard contributions per year. {{ implode(', ', array_column($points, 'text')) }}." class="block overflow-visible {{ $theme->link }}">
<line x1="4" y1="50" x2="236" y2="50" stroke="#cbd5e1" stroke-width="1"></line>
<path d="{{ $line }} L236 50 L4 50 Z" fill="currentColor" fill-opacity="0.12"></path>
<path d="{{ $line }}" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"></path>
@foreach (array_unique([$peak, $last]) as $year)
<text x="{{ $points[$year]['x'] }}" y="{{ $points[$year]['y'] - 8 }}" font-size="11" fill="#334155" text-anchor="{{ $year === $first ? 'start' : ($year === $last ? 'end' : 'middle') }}">{{ number_format($speaking[$year]) }}</text>
@endforeach
<circle cx="{{ $points[$last]['x'] }}" cy="{{ $points[$last]['y'] }}" r="4" fill="currentColor" stroke="#ffffff" stroke-width="2"></circle>
@foreach ($points as $point)
<g class="group">
<title>{{ $point['text'] }}</title>
<rect x="{{ $point['x'] - $step / 2 }}" y="0" width="{{ $step }}" height="56" fill="transparent"></rect>
<circle class="opacity-0 group-hover:opacity-100" cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="4" fill="currentColor" stroke="#ffffff" stroke-width="2"></circle>
</g>
@endforeach
</svg>
<div aria-hidden="true" class="flex w-[240px] justify-between text-xs !text-slate-600"><span>{{ $first }}</span><span>{{ $last }}</span></div>
</div>
</section>
@endif
