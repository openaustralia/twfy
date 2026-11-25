@use('OpenAustralia\TWFY\Enums\MemberOrder')
{{-- Scrolls sideways when the table is wider than the screen. --}}
<div class="overflow-x-auto">
<table class="people !m-0 w-full" cellpadding="4" cellspacing="0">
<thead>
<tr>
<th>Photo</th>
<th>
@include('members.sort-link', ['by' => MemberOrder::FirstName, 'label' => 'First'])
&amp;
@include('members.sort-link', ['by' => MemberOrder::LastName, 'label' => 'Last'])
name
</th>
<th>@include('members.sort-link', ['by' => MemberOrder::Party, 'label' => 'Party'])</th>
<th>@include('members.sort-link', ['by' => MemberOrder::Constituency, 'label' => $area])</th>
<th>Positions</th>
@if ($order === MemberOrder::Debates)
<th>Debates spoken in the last year</th>
@endif
</tr>
</thead>
<tbody>
@foreach ($members as $member)
@php($row = 'row-' . ($loop->odd ? 1 : 2))
@php($photo = $member->photo_url)
<tr>
<td class="row">
@if ($photo)
<a href="{{ $member->profile_url }}"><img class="portrait" alt="" src="{{ $photo }}"></a>
@endif
</td>
<td class="{{ $row }}"><a href="{{ $member->profile_url }}">{{ ucfirst($member->full_name) }}</a></td>
<td class="{{ $row }}">{{ $member->party_label }}</td>
<td class="{{ $row }}">{{ $member->display_constituency }}</td>
<td class="{{ $row }}">
@foreach ($member->positions as $position)
<div>{{ $position }}</div>
@endforeach
</td>
@if ($order === MemberOrder::Debates)
<td class="{{ $row }}">{{ number_format((int) $member->personInfo->first()->data_value) }}</td>
@endif
</tr>
@endforeach
</tbody>
</table>
</div>
