<x-admin.table :headings="['Query', 'Page', 'Hits', 'Host', 'Time ago']" :numeric="[1, 2]">
@foreach ($searches as $search)
<x-admin.row>
<x-admin.cell><x-admin.link :href="$search['url']">{{ $search['query'] }}</x-admin.link></x-admin.cell>
<x-admin.cell numeric>{{ $search['page'] }}</x-admin.cell>
<x-admin.cell numeric>{{ $search['hits'] }}</x-admin.cell>
<x-admin.cell>{{ $search['host'] }}</x-admin.cell>
<x-admin.cell>{{ $search['ago'] }}</x-admin.cell>
</x-admin.row>
@endforeach
</x-admin.table>
