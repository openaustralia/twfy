<x-admin.heading>Stats</x-admin.heading>
<ul>
<li>Confirmed users: {{ $confirmed }}</li>
<li>Unconfirmed users: {{ $unconfirmed }}</li>
<li>Logged-in users active in past day: {{ $activeDay }}</li>
<li>Logged-in users active in past week: {{ $activeWeek }}</li>
</ul>

<x-admin.heading>Recently registered users</x-admin.heading>
<x-admin.table :headings="['Name', 'Email', 'Confirmed?', 'Registration time']">
@foreach ($recent as $user)
<x-admin.row>
<x-admin.cell>
@if ($user['url'])
<x-admin.link :href="$user['url']">{{ $user['name'] }}</x-admin.link>
@else
{{ $user['name'] }}
@endif
</x-admin.cell>
<x-admin.cell><x-admin.link :href="'mailto:' . $user['email']">{{ $user['email'] }}</x-admin.link></x-admin.cell>
<x-admin.cell>{{ $user['confirmed'] }}</x-admin.cell>
<x-admin.cell>{{ $user['registered'] }}</x-admin.cell>
</x-admin.row>
@endforeach
</x-admin.table>
