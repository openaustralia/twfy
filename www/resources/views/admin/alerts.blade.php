<x-admin.heading>Statistics</x-admin.heading>
<x-admin.table :headings="['Stat', 'Number']" :numeric="[1]">
@foreach ($totals as [$label, $count])
<x-admin.row>
<x-admin.cell>{{ $label }}</x-admin.cell>
<x-admin.cell numeric>{{ $count }}</x-admin.cell>
</x-admin.row>
@endforeach
</x-admin.table>

@foreach ($lists as $title => $rows)
<x-admin.heading>{{ $title }}</x-admin.heading>
<x-admin.table :headings="[['label' => 'Email', 'href' => 'alerts.php'], 'Criteria', ['label' => 'Created', 'href' => 'alerts.php?o=c']]" empty="There are none.">
@foreach ($rows as $alert)
<x-admin.row>
<x-admin.cell>
@if ($alert['url'])
<x-admin.link :href="$alert['url']">{{ $alert['who'] }}</x-admin.link>
@else
{{ $alert['who'] }}
@endif
</x-admin.cell>
<x-admin.cell>{{ $alert['description'] }}</x-admin.cell>
<x-admin.cell>{{ $alert['created'] }}</x-admin.cell>
</x-admin.row>
@endforeach
</x-admin.table>
@endforeach
