<x-admin.breadcrumbs :trail="[['Admin', '/admin/'], ['Busiest API keys', null]]" />
<x-admin.filters :choices="$filters" />
<x-admin.note class="max-w-3xl">Days are UTC, from {{ $from }} to {{ $to }}. API calls are counted as they happen.</x-admin.note>

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
