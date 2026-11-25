<x-admin.table :headings="['Distinct IPs', 'Number of times', 'Query', 'Most recent ago', 'First ago']" :numeric="[0, 1]">
@foreach ($searches as $search)
<x-admin.row>
<x-admin.cell numeric>{{ $search['ips'] }}</x-admin.cell>
<x-admin.cell numeric>{{ $search['times'] }}</x-admin.cell>
<x-admin.cell><x-admin.link :href="$search['url']">{{ $search['query'] }}</x-admin.link></x-admin.cell>
<x-admin.cell>{{ $search['latest'] }}</x-admin.cell>
<x-admin.cell>{{ $search['first'] }}</x-admin.cell>
</x-admin.row>
@endforeach
</x-admin.table>
