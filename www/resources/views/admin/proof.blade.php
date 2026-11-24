<x-admin.heading>Import status of each year</x-admin.heading>
<x-admin.breadcrumbs :trail="[['Admin', '/admin/'], ['Import status', null]]" />
<x-admin.note class="max-w-3xl">Each bar shows the share of the sitting days we hold for a year. A sitting day is one house's sitting, so a date when both houses sat counts twice. Choose a year to see its months and days.</x-admin.note>
<x-admin.proof-legend :segments="$segments" />
<ol class="max-w-4xl !m-0 p-0 list-none">
@foreach ($years as $year)
<li class="flex items-center gap-4 !ml-0 px-3 py-2 rounded hover:bg-slate-50">
@if ($year['url'])
<x-admin.link :href="$year['url']" class="w-14 text-sm font-semibold">{{ $year['year'] }}</x-admin.link>
@else
<span class="w-14 text-sm text-slate-400">{{ $year['year'] }}</span>
@endif
<x-admin.proof-bar :bar="$year['bar']" />
<span class="w-24 text-right text-sm tabular-nums text-slate-700">{{ $year['share'] }}</span>
<span class="w-24 text-right text-xs tabular-nums text-slate-500">{{ $year['days'] }}</span>
<span class="w-28 text-right text-xs tabular-nums {{ $year['has_errors'] ? 'font-semibold text-rose-700' : 'text-slate-400' }}">{{ $year['error_days'] }}</span>
</li>
@endforeach
</ol>
