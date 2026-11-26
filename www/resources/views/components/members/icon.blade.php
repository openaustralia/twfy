{{-- A 24 by 24 line icon in the colour of the text around it. Screen readers skip it. --}}
@props(['path', 'size' => 20])
<svg aria-hidden="true" {{ $attributes->class('flex-none') }} width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $path }}"></path></svg>
