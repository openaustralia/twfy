<x-admin.breadcrumbs :trail="$trail" />
<div class="flex flex-wrap items-center justify-between gap-6 text-sm font-semibold">
@if ($newer)<x-admin.link :href="$newer['url']">&larr; {{ $newer['year'] }}</x-admin.link>@else<span></span>@endif
@if ($older)<x-admin.link :href="$older['url']">{{ $older['year'] }} &rarr;</x-admin.link>@endif
</div>
<div class="flex items-center gap-4 my-4">
<x-admin.proof-bar :bar="$year_bar" />
<span class="w-24 text-right text-sm tabular-nums text-slate-700">{{ $year_share }}</span>
</div>
<x-admin.proof-legend :segments="$segments" />
<x-admin.note class="max-w-3xl">Each row is a month. A sitting day is one house's sitting, so a date when both houses sat counts twice. Choose a month to see its days. "Last changed" is when a row last changed in our database, not when we last read it from Parliament House.</x-admin.note>

<x-admin.table :headings="array_merge(['Month', 'Share of sitting days'], array_column($columns, 'label'), ['Sitting days', 'Days with errors', 'Last changed'])" :numeric="range(2, count($columns) + 3)">
@foreach ($months as $row)
<x-admin.row :class="$row['selected'] ? 'bg-slate-100' : ''">
<td class="px-3 py-2 whitespace-nowrap border-0 border-l-4 border-solid {{ $row['selected'] ? 'border-teal-700' : 'border-transparent' }}">
@if ($row['url'])
<x-admin.link :href="$row['url']" class="font-semibold" :aria-current="$row['selected'] ? 'true' : null">{{ $row['name'] }}</x-admin.link>
@else
<span class="text-slate-400">{{ $row['name'] }}</span>
@endif
</td>
<td class="px-3 py-2 w-48"><div class="flex"><x-admin.proof-bar :bar="$row['bar']" /></div></td>
@foreach ($row['counts'] as $count)
<x-admin.cell numeric>{{ $count }}</x-admin.cell>
@endforeach
<x-admin.cell numeric>{{ $row['total'] }}</x-admin.cell>
<x-admin.cell numeric class="{{ $row['errors'] > 0 ? 'font-semibold text-rose-700' : 'text-slate-400' }}">{{ $row['errors'] }}</x-admin.cell>
<x-admin.cell class="text-slate-600 whitespace-nowrap tabular-nums">{{ $row['last_changed'] }}</x-admin.cell>
</x-admin.row>
@endforeach
</x-admin.table>

@if ($month)
<h3 id="days" class="mt-8 mb-4 mx-0 text-base font-semibold text-slate-900">{{ $month }}</h3>
<x-admin.table :headings="['Date', 'House', 'Status', 'Proof rows', 'Final rows', 'Unknown rows', 'Errors', 'Last changed']" :numeric="[3, 4, 5, 6]" empty="We hold no sitting days in this month.">
@foreach ($days as $day)
<x-admin.row :alert="$day['errors'] > 0">
<x-admin.cell class="whitespace-nowrap"><x-admin.link :href="$day['url']">{{ $day['date'] }}</x-admin.link></x-admin.cell>
<x-admin.cell class="text-slate-700">{{ $day['house'] }}</x-admin.cell>
<x-admin.cell>
<x-admin.pill :colours="$day['status_colours']">@if ($day['status_key'])<x-admin.proof-icon :name="$day['status_key']" :size="12" />@endif{{ $day['status'] }}</x-admin.pill>
</x-admin.cell>
<x-admin.cell numeric>{{ $day['proof'] }}</x-admin.cell>
<x-admin.cell numeric>{{ $day['final'] }}</x-admin.cell>
<x-admin.cell numeric>{{ $day['unknown'] }}</x-admin.cell>
<x-admin.cell numeric>
@if ($day['errors_url'])
<a class="inline-flex items-center gap-1 whitespace-nowrap font-semibold !text-rose-700 !no-underline hover:!underline" href="{{ $day['errors_url'] }}"><x-admin.proof-icon name="errors" :size="16" />{{ $day['errors_label'] }}</a>
@endif
</x-admin.cell>
<x-admin.cell class="text-slate-600 whitespace-nowrap tabular-nums">{{ $day['last_changed'] }}</x-admin.cell>
</x-admin.row>
@endforeach
</x-admin.table>
@endif
