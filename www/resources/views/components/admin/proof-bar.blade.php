{{-- A thin bar split into the share of days in each state. $bar comes from ProofController::bar(). --}}
@props(['bar'])
@if (!$bar['parts'])
<div class="flex flex-1 h-3 rounded bg-slate-100" role="img" aria-label="{{ $bar['label'] }}"></div>
@else
<div class="flex flex-1 gap-0.5 h-3 overflow-hidden rounded bg-white" role="img" aria-label="{{ $bar['label'] }}">
@foreach ($bar['parts'] as $part)
<div class="{{ $part['class'] }}" style="width:{{ $part['width'] }}%;min-width:3px" title="{{ $part['title'] }}"></div>
@endforeach
</div>
@endif
