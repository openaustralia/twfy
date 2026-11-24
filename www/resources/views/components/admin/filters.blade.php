{{-- Choices that change what the page shows, each [label, url, whether it is the current one]. --}}
@props(['choices'])
<ul class="flex flex-wrap items-center gap-6 !m-0 p-0 list-none text-sm font-semibold">
@foreach ($choices as [$label, $url, $current])
@if ($current)
<li class="!ml-0 text-slate-900" aria-current="page">{{ $label }}</li>
@else
<li class="!ml-0"><x-admin.link :href="$url">{{ $label }}</x-admin.link></li>
@endif
@endforeach
</ul>
