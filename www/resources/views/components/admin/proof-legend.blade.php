{{-- The key of colours, one entry for each segment, keyed by the segment's name. --}}
@props(['segments'])
<ul class="flex flex-wrap gap-x-5 gap-y-1 my-4 p-0 list-none text-sm text-slate-700">
@foreach ($segments as $key => $segment)
<li class="flex items-center gap-2"><span class="inline-flex items-center justify-center w-5 h-5 rounded text-white {{ $segment['bar'] }}"><x-admin.proof-icon :name="$key" :size="14" /></span>{{ $segment['label'] }}</li>
@endforeach
</ul>
