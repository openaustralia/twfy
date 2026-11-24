@props(['headings', 'numeric' => [], 'empty' => ''])
@if ($empty !== '' && trim((string) $slot) === '')
<p class="text-sm text-slate-600">{{ $empty }}</p>
@else
{{-- A raised card that scrolls sideways when the table is wider than the page. --}}
<div {{ $attributes->merge(['class' => 'max-w-4xl overflow-x-auto rounded-lg bg-white shadow-md']) }}>
<table class="w-full !m-0 text-sm border-collapse">
<thead><tr class="bg-slate-100 text-left text-xs uppercase tracking-wide text-slate-600">
@foreach ($headings as $index => $heading)
<th class="px-3 py-2 font-semibold{{ in_array($index, $numeric, true) ? ' text-right' : '' }}">{{ $heading }}</th>
@endforeach
</tr></thead>
<tbody>
{{ $slot }}
</tbody>
</table>
</div>
@endif
