<x-admin.heading>Users and their roles</x-admin.heading>
<x-admin.breadcrumbs :trail="[['Admin', '/admin/'], ['Users', null]]" />
<div class="mb-4"><x-admin.filters :choices="$filters" /></div>
<x-admin.note class="max-w-3xl">Administrators and Superusers can open the admin pages, and Moderators can moderate comments. {{ $can_edit ? 'Nobody can change their own role, so there is always a Superuser.' : 'Only a Superuser can change roles.' }}</x-admin.note>
@if ($result)
<p role="status" class="rounded px-3 py-2 text-sm {{ $result['good'] ? 'bg-teal-100 text-teal-800' : 'bg-rose-100 text-rose-800' }}">{{ $result['text'] }}</p>
@endif
<x-admin.table :headings="['Name', 'Email', 'Role', 'Account']" :empty="$role === '' ? 'There are no users.' : 'No users have this role.'">
@foreach ($users as $user)
<x-admin.row>
<x-admin.cell><x-admin.link :href="$user['url']">{{ $user['name'] }}</x-admin.link></x-admin.cell>
<x-admin.cell>{{ $user['email'] }}</x-admin.cell>
<x-admin.cell>
@if ($user['editable'])
<form method="post" action="{{ $form_url }}" class="flex items-center gap-2 !m-0">
<input type="hidden" name="user_id" value="{{ $user['id'] }}">
<input type="hidden" name="token" value="{{ $token }}">
<select name="new_role" aria-label="Role for {{ $user['name'] }}" class="rounded border border-solid border-slate-300 bg-white px-2 py-1 text-sm">
@foreach ($roles as $each)
<option @selected($each === $user['role'])>{{ $each }}</option>
@endforeach
</select>
<button type="submit" class="rounded bg-teal-700 px-3 py-1 text-xs font-semibold !text-white hover:bg-teal-600">Save</button>
</form>
@else
<x-admin.role :role="$user['role']" />@if ($user['is_you']) <span class="text-xs text-slate-500">(you)</span>@endif
@endif
</x-admin.cell>
<x-admin.cell>{{ $user['account'] }}</x-admin.cell>
</x-admin.row>
@endforeach
</x-admin.table>
@if ($pages > 1)
<nav aria-label="Pages of users" class="flex items-center justify-between gap-6 mt-4 text-sm font-semibold">
@if ($previous_url)<x-admin.link :href="$previous_url">&larr; Previous</x-admin.link>@else<span></span>@endif
<span class="text-slate-500">Page {{ $page }} of {{ $pages }}</span>
@if ($next_url)<x-admin.link :href="$next_url">Next &rarr;</x-admin.link>@else<span></span>@endif
</nav>
@endif

<x-admin.heading>Recent role changes</x-admin.heading>
<x-admin.table :headings="['When (UTC)', 'User', 'Role', 'Changed by']" empty="No roles have been changed here yet.">
@foreach ($changes as $change)
<x-admin.row>
<x-admin.cell>{{ $change['when'] }}</x-admin.cell>
<x-admin.cell>@if ($change['user'])<x-admin.link :href="$change['user']['url']">{{ $change['user']['name'] }}</x-admin.link>@else Unknown user @endif</x-admin.cell>
<x-admin.cell><x-admin.role :role="$change['from']" /> &rarr; <x-admin.role :role="$change['to']" /></x-admin.cell>
<x-admin.cell>@if ($change['by'])<x-admin.link :href="$change['by']['url']">{{ $change['by']['name'] }}</x-admin.link>@else Unknown user @endif</x-admin.cell>
</x-admin.row>
@endforeach
</x-admin.table>
