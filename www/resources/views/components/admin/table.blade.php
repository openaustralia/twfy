{{-- A heading is its text, or ['label' => ..., 'href' => ...] to make it a link. --}}
@props(['headings', 'numeric' => [], 'empty' => ''])
@if ($empty !== '' && trim((string) $slot) === '')
<p class="text-sm text-slate-600">{{ $empty }}</p>
@else
{{-- A raised card that scrolls sideways when the table is wider than the page. --}}
<div {{ $attributes->merge(['class' => 'mb-8 overflow-x-auto rounded-lg bg-white shadow-md']) }}>
<table class="w-full !m-0 text-sm border-collapse">
<thead><tr class="bg-slate-100 text-left text-xs uppercase tracking-wide text-slate-600">
@foreach ($headings as $index => $heading)
<th class="px-3 py-2 font-semibold{{ in_array($index, $numeric, true) ? ' text-right' : '' }}">
@if (is_array($heading))
<x-admin.link :href="$heading['href']">{{ $heading['label'] }}</x-admin.link>
@else
{{ $heading }}
@endif
</th>
@endforeach
</tr></thead>
<tbody>
{{ $slot }}
</tbody>
</table>
</div>
@endif
