<x-admin.heading>People who probably wanted separate signups</x-admin.heading>
<x-admin.table class="mx-4" :headings="['Alert', 'Criteria']" empty="There are none.">
@foreach ($separate as [$id, $criteria])
<x-admin.row>
<x-admin.cell numeric>{{ $id }}</x-admin.cell>
<x-admin.cell>{{ $criteria }}</x-admin.cell>
</x-admin.row>
@endforeach
</x-admin.table>

<x-admin.heading>Alert signups by MP</x-admin.heading>
<x-admin.table class="mx-4" :headings="['Name', 'Confirmed', 'Unconfirmed']" :numeric="[1, 2]" empty="There are none.">
@foreach ($speakers as [$name, $confirmed, $unconfirmed])
<x-admin.row>
<x-admin.cell>{{ $name }}</x-admin.cell>
<x-admin.cell numeric>{{ $confirmed }}</x-admin.cell>
<x-admin.cell numeric>{{ $unconfirmed }}</x-admin.cell>
</x-admin.row>
@endforeach
</x-admin.table>

<x-admin.heading>Alert signups by keyword</x-admin.heading>
<x-admin.table class="mx-4" :headings="['Criteria', 'Confirmed', 'Unconfirmed']" :numeric="[1, 2]" empty="There are none.">
@foreach ($keywords as [$criteria, $confirmed, $unconfirmed])
<x-admin.row>
<x-admin.cell>{{ $criteria }}</x-admin.cell>
<x-admin.cell numeric>{{ $confirmed }}</x-admin.cell>
<x-admin.cell numeric>{{ $unconfirmed }}</x-admin.cell>
</x-admin.row>
@endforeach
</x-admin.table>
