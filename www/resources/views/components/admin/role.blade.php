{{-- A user's role. Staff roles get a pill, and Viewer and User are plain text. --}}
@props(['role'])
@php
$colours = ['Superuser' => 'bg-violet-100 text-violet-800', 'Administrator' => 'bg-teal-100 text-teal-800', 'Moderator' => 'bg-amber-100 text-amber-800'][$role] ?? null;
@endphp
@if ($colours)<x-admin.pill :colours="$colours">{{ $role }}</x-admin.pill>@else{{ $role }}@endif
