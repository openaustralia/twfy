@use('Illuminate\Support\Str')
@use('OpenAustralia\TWFY\Parliament\MemberUrls')
@php($link = $theme->link . ' !underline')
@php($offices = $profile->pastOffices())
<aside aria-label="More about this person" class="mt-8 flex flex-col gap-5 lg:mt-0 lg:w-80 lg:flex-none">
@if ($links)
<x-members.panel title="More useful links for this person">
<ul class="!m-0 !pl-5 text-base !text-slate-700">
@foreach ($links as $each)
<li><a href="{{ $each['url'] }}" class="{{ $link }}">{{ $each['text'] }}</a>
@if ($each['source'])
<small>({{ $each['source'] }})</small>
@endif
</li>
@endforeach
</ul>
</x-members.panel>
@endif
@if ($offices)
<x-members.panel title="Other offices held in the past">
<x-members.offices :offices="array_slice($offices, 0, 5)" />
@if (count($offices) > 5)
<details class="border-0 border-t border-solid border-slate-200">
<summary class="flex min-h-[44px] cursor-pointer items-center text-base font-semibold {{ $theme->link }}">Show {{ count($offices) - 5 }} more {{ Str::plural('office', count($offices) - 5) }}</summary>
<x-members.offices :offices="array_slice($offices, 5)" />
</details>
@endif
</x-members.panel>
@endif
@foreach (['Previous' => $profile->predecessors(), 'Succeeding' => $profile->successors()] as $which => $neighbours)
@if ($neighbours->isNotEmpty())
<x-members.panel :title="$which . ' Representatives in this constituency'">
<ul class="!m-0 !pl-5 text-base !text-slate-700">
@foreach ($neighbours as $neighbour)
<li><a href="{{ MemberUrls::person($neighbour->person_id) }}" class="{{ $link }}">{{ $neighbour->short_name }}</a></li>
@endforeach
</ul>
</x-members.panel>
@endif
@endforeach
<x-members.panel title="Journalist?" soft>
<p class="!m-0 text-[15px] !text-slate-700">Please feel free to use the data on this page, but if you do you must cite OpenAustralia.org in the body of your articles as the source of any analysis or data you get off this site.</p>
</x-members.panel>
@include('members.show.donate')
</aside>
