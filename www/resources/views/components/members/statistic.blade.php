{{-- One number on a profile. The slot says what it counts, $standing how it compares, and $action what to do. --}}
@props(['figure', 'theme', 'standing' => null, 'action' => null])
<li class="!m-0 flex flex-col items-start gap-1 rounded-lg border border-solid border-slate-300 bg-white p-4">
<strong class="text-3xl font-bold leading-tight !text-slate-900">{{ $figure }}</strong>
<span class="text-[15px] !text-slate-700">{{ $slot }}</span>
@if ($standing)
<span class="mt-1 rounded-full px-2.5 py-0.5 text-[13px] font-semibold {{ $theme->icon }}">{{ ucfirst($standing) }}</span>
@endif
@if ($action)
<div class="mt-2">{{ $action }}</div>
@endif
</li>
