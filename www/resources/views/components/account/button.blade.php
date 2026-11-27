{{-- A button, or with $href a link that looks like one. $variant is primary or secondary. --}}
@props(['variant' => 'secondary', 'href' => null, 'type' => 'submit'])
@php
$class = 'box-border inline-flex min-h-[44px] cursor-pointer items-center gap-2 rounded px-5 py-2.5 text-base font-semibold !no-underline '
    . ($variant === 'primary'
        ? 'border-0 bg-teal-700 !text-white shadow hover:bg-teal-800'
        : 'border border-solid border-slate-500 bg-white !text-slate-900 hover:bg-slate-50');
@endphp
@if ($href)
<a href="{{ $href }}" {{ $attributes->merge(['class' => $class]) }}>{{ $slot }}</a>
@else
<button type="{{ $type }}" {{ $attributes->merge(['class' => $class]) }}>{{ $slot }}</button>
@endif
