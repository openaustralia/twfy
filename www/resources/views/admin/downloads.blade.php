<x-admin.breadcrumbs :trail="[['Admin', '/admin/'], ['Data site downloads', null]]" />
<x-admin.filters :choices="$filters" />
<x-admin.note class="max-w-3xl">Days are UTC, from {{ $from }} to {{ $to }}. Downloads are imported from data.openaustralia.org.au's access log each night, so they run to yesterday.</x-admin.note>

<x-admin.heading>Data site downloads per day</x-admin.heading>
<x-admin.table :headings="['Day', 'Downloads', 'From OAF servers']" :numeric="[1, 2]" empty="No downloads imported for this period.">
@foreach ($daily as $day)
<x-admin.row>
<x-admin.cell>{{ $day['day'] }}</x-admin.cell>
<x-admin.cell numeric>{{ number_format($day['downloads']) }}</x-admin.cell>
<x-admin.cell numeric>{{ number_format($day['internal']) }}</x-admin.cell>
</x-admin.row>
@endforeach
</x-admin.table>

<x-admin.heading>Most downloaded files</x-admin.heading>
<x-admin.table :headings="['Path', 'Downloads']" :numeric="[1]" empty="No downloads imported for this period.">
@foreach ($paths as $path)
<x-admin.row>
<x-admin.cell>{{ $path['path'] }}</x-admin.cell>
<x-admin.cell numeric>{{ number_format($path['downloads']) }}</x-admin.cell>
</x-admin.row>
@endforeach
</x-admin.table>

<x-admin.heading>Busiest visitors</x-admin.heading>
<x-admin.table :headings="['IP address', 'User agent', 'Downloads', 'OAF server']" :numeric="[2]" empty="No downloads imported for this period.">
@foreach ($visitors as $visitor)
<x-admin.row>
<x-admin.cell>{{ $visitor['ip_address'] }}</x-admin.cell>
<x-admin.cell>{{ $visitor['user_agent'] }}</x-admin.cell>
<x-admin.cell numeric>{{ number_format($visitor['downloads']) }}</x-admin.cell>
<x-admin.cell>{{ $visitor['internal'] ? 'Yes' : 'No' }}</x-admin.cell>
</x-admin.row>
@endforeach
</x-admin.table>
