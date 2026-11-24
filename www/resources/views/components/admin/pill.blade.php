{{-- A rounded label. The colours are Tailwind classes, such as 'bg-teal-100 text-teal-800'. --}}
@props(['colours' => 'bg-slate-100 text-slate-700'])
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold ' . $colours]) }}>{{ $slot }}</span>
