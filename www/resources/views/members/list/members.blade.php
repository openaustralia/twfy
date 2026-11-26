@use('Illuminate\Support\Str')
@use('OpenAustralia\TWFY\Enums\MemberOrder')
@php($count = $members->count())
@php($photo = 'box-border h-[86px] w-16 flex-none rounded-md border-2 border-solid ' . $theme->photo)
<p aria-live="polite" class="!m-0 !mt-3 text-sm !text-slate-600">{{ $count }} {{ Str::plural($house->memberTitle(), $count) }}</p>
{{-- On a phone the headings are a "Sort by" strip, as each row labels its own values. --}}
<ul class="!mx-0 !mb-0 !mt-3 flex list-none flex-wrap items-baseline gap-x-5 gap-y-1 border-0 border-b border-solid border-slate-300 bg-slate-50 !p-0 !py-2.5 text-sm font-bold">
<li class="!m-0 md:hidden">Sort by</li>
<li aria-hidden="true" class="!m-0 hidden w-16 flex-none md:block"></li>
<li class="!m-0 min-w-0 md:flex-[1_1_160px]">
@include('members.list.sort-link', ['by' => MemberOrder::FirstName, 'label' => 'First'])
&amp;
@include('members.list.sort-link', ['by' => MemberOrder::LastName, 'label' => 'Last name'])
</li>
<li class="!m-0 min-w-0 md:flex-[1_1_150px]">@include('members.list.sort-link', ['by' => MemberOrder::Party, 'label' => 'Party'])</li>
<li class="!m-0 min-w-0 md:flex-[1_1_130px]">@include('members.list.sort-link', ['by' => MemberOrder::Constituency, 'label' => $area])</li>
<li class="!m-0 hidden min-w-0 md:block md:flex-[1.4_1_200px]">Positions</li>
</ul>
<ul class="!m-0 list-none !p-0">
@forelse ($members as $member)
<li class="!m-0 flex flex-wrap items-center gap-x-5 gap-y-3 border-0 border-b border-solid border-slate-200 py-4">
@if ($member->photo_url)
<img src="{{ $member->photo_url }}" alt="" width="64" height="86" class="{{ $photo }} object-cover">
@else
<span aria-hidden="true" class="{{ $photo }}"></span>
@endif
<div class="min-w-0 flex-[1_1_160px]"><a href="{{ $member->profile_url }}" class="text-[19px] font-bold {{ $theme->link }} !underline">{{ ucfirst($member->full_name) }}</a></div>
<div class="min-w-0 flex-[1_1_100%] text-[15px] md:flex-[1_1_150px]"><span class="font-semibold md:hidden">Party: </span>{{ $member->party_label }}</div>
<div class="min-w-0 flex-[1_1_100%] text-[15px] md:flex-[1_1_130px]"><span class="font-semibold md:hidden">{{ $area }}: </span>{{ $member->display_constituency }}</div>
<div class="min-w-0 flex-[1_1_100%] text-[15px] md:flex-[1.4_1_200px]">
<span class="font-semibold md:hidden">Positions:</span>
@forelse ($member->positions as $position)
<div>{{ $position }}</div>
@empty
<span class="text-sm !text-slate-600">None</span>
@endforelse
</div>
</li>
@empty
<li class="!m-0 my-6 rounded-lg bg-slate-50 p-5 text-base">No {{ strtolower($house->memberTitle()) }}s match your search. Try a different name or clear the party filter.</li>
@endforelse
</ul>
