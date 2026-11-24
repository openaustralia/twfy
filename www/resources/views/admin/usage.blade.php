<x-admin.heading>API and data site usage</x-admin.heading>
<x-admin.breadcrumbs :trail="[['Admin', '/admin/'], ['API and data site usage', null]]" />
<x-admin.filters :choices="$filters" />
<x-admin.note class="max-w-3xl">Days are UTC, from {{ $from }} to {{ $to }}. API calls are counted as they happen. Downloads are imported from data.openaustralia.org.au's access log each night, so they run to yesterday.</x-admin.note>

<x-admin.heading>Busiest API keys</x-admin.heading>
<x-admin.table :headings="['Name', 'Email', 'Reason', 'Commercial', 'Disabled', 'Calls', 'Mean per day', 'Fewest', 'Most']" :numeric="[5, 6, 7, 8]" empty="No API calls with a known key in this period.">
@foreach ($keys as $key)
<x-admin.row>
<x-admin.cell>
@if ($key['name'] === null)
<em>No account</em>
@elseif ($key['website'])
<x-admin.link :href="$key['website']">{{ $key['name'] }}</x-admin.link>
@else
{{ $key['name'] }}
@endif
</x-admin.cell>
<x-admin.cell>{{ $key['email'] }}</x-admin.cell>
<x-admin.cell><span title="{{ $key['reason'] }}">{{ $key['short_reason'] }}</span></x-admin.cell>
<x-admin.cell>{{ $key['commercial'] ? 'Yes' : 'No' }}</x-admin.cell>
<x-admin.cell>{{ $key['disabled'] }}</x-admin.cell>
<x-admin.cell numeric>{{ number_format($key['total']) }}</x-admin.cell>
<x-admin.cell numeric>{{ number_format($key['mean'], 1) }}</x-admin.cell>
<x-admin.cell numeric>{{ number_format($key['min']) }}</x-admin.cell>
<x-admin.cell numeric>{{ number_format($key['max']) }}</x-admin.cell>
</x-admin.row>
@endforeach
</x-admin.table>

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
