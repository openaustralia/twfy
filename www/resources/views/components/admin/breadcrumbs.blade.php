{{-- The trail is a list of [label, url or null]. The last step is the page you are on. --}}
@props(['trail'])
<nav aria-label="Breadcrumb" class="mb-4"><ol class="flex flex-wrap items-center gap-2 !m-0 p-0 list-none text-sm text-slate-500">
@foreach ($trail as $index => [$label, $url])
@if ($loop->last || $url === null)
<li class="!ml-0 font-semibold text-slate-900" aria-current="page">{{ $label }}</li>
@else
<li class="!ml-0 flex items-center gap-2"><x-admin.link :href="$url">{{ $label }}</x-admin.link><span aria-hidden="true">&rsaquo;</span></li>
@endif
@endforeach
</ol></nav>
