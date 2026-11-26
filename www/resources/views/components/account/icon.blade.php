{{-- A line icon drawn from an SVG path, hidden from screen readers. --}}
@props(['path', 'size' => 18])
<svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false" class="shrink-0"><path d="{{ $path }}" /></svg>
