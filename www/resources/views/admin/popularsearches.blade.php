<x-admin.table :headings="['Query', 'Count']" :numeric="[1]">
@foreach ($searches as $search)
<x-admin.row>
<x-admin.cell><x-admin.link :href="$search['url']">{{ $search['query'] }}</x-admin.link></x-admin.cell>
<x-admin.cell numeric>{{ $search['count'] }}</x-admin.cell>
</x-admin.row>
@endforeach
</x-admin.table>
