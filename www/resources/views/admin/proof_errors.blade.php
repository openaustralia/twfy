<x-admin.breadcrumbs :trail="$trail" />
@if (!$valid)
<x-admin.note>That is not a day and house we can show errors for.</x-admin.note>
@else
<h3 class="mt-0 mb-2 mx-0 text-base font-semibold text-slate-900">Import errors for {{ $date }}, {{ $house }}</h3>
<x-admin.note class="max-w-3xl">The parser records errors while it reads a day from Parliament House, and the loader records them while it puts the day into the database. Each clears its own errors for a day when it next handles the day without one.</x-admin.note>
<ul class="my-4 pl-5 text-sm">
@foreach ($links as $label => $url)
<li><x-admin.link :href="$url">{{ $label }}</x-admin.link></li>
@endforeach
</ul>
@if (!$errors)
<x-admin.note>No errors are recorded for this day.</x-admin.note>
@else
<x-admin.table :headings="['Found by', 'Error', 'Recorded']">
@foreach ($errors as $error)
<x-admin.row class="align-top">
<x-admin.cell class="whitespace-nowrap">{{ $error['source'] === 'parser' ? 'Parser' : 'Loader' }}</x-admin.cell>
<x-admin.cell><pre class="m-0 whitespace-pre-wrap break-words font-mono text-xs text-slate-900">{{ $error['message'] }}</pre></x-admin.cell>
<x-admin.cell class="text-slate-600 whitespace-nowrap tabular-nums">{{ $error['created_at'] ?? '' }}</x-admin.cell>
</x-admin.row>
@endforeach
</x-admin.table>
<p class="mt-4 text-sm font-semibold"><x-admin.link :href="$month_url">&larr; Back to {{ $month }}</x-admin.link></p>
@endif
@endif
