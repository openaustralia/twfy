{{-- A 16 by 16 outline icon for a proof segment, drawn in the colour of the text around it. Screen readers skip it. --}}
@props(['name', 'size' => 16])
<svg class="inline-block shrink-0" width="{{ $size }}" height="{{ $size }}" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
@switch($name)
@case('final')
<circle cx="8" cy="8" r="6.5"/><path d="M5 8.2l2 2 4-4.4" stroke-linecap="round" stroke-linejoin="round"/>
@break
@case('proof')
<path d="M3 13l.6-2.8L10.8 3l2.2 2.2-7.2 7.2L3 13z" stroke-linejoin="round"/><path d="M9.6 4.2l2.2 2.2"/>
@break
@case('mixed')
<circle cx="8" cy="8" r="6.5"/><path d="M8 1.5a6.5 6.5 0 0 1 0 13z" fill="currentColor"/>
@break
@case('unknown')
<circle cx="8" cy="8" r="6.5"/><path d="M6.2 6.3a1.9 1.9 0 1 1 2.7 1.7c-.6.3-.9.7-.9 1.3M8 11.3v.01" stroke-linecap="round"/>
@break
@case('errors')
<circle cx="8" cy="8" r="6.5"/><path d="M8 4.5v4M8 11v.01" stroke-linecap="round"/>
@break
@endswitch
</svg>
