@use('OpenAustralia\TWFY\Parliament\MemberUrls')
<aside class="mt-8 space-y-4 lg:mt-0 lg:w-72 lg:shrink-0">
@include('members.donate')
@if ($links)
<x-members.card title="More useful links for this person">
<ul class="!m-0 !pl-5 text-sm">
@foreach ($links as $link)
<li><a href="{{ $link['url'] }}">{{ $link['text'] }}</a>
@if ($link['source'])
<small>({{ $link['source'] }})</small>
@endif
</li>
@endforeach
</ul>
</x-members.card>
@endif
@foreach (['Previous' => $profile->predecessors(), 'Succeeding' => $profile->successors()] as $which => $neighbours)
@if ($neighbours->isNotEmpty())
<x-members.card :title="$which . ' Representatives in this constituency'">
<ul class="!m-0 !pl-5 text-sm">
@foreach ($neighbours as $neighbour)
<li><a href="{{ MemberUrls::person($neighbour->person_id) }}">{{ $neighbour->short_name }}</a></li>
@endforeach
</ul>
</x-members.card>
@endif
@endforeach
@if ($profile->pastOffices())
<x-members.card title="Other offices held in the past">
<ul class="!m-0 !pl-5 text-sm">
@foreach ($profile->pastOffices() as $office)
<li>{{ $office }}</li>
@endforeach
</ul>
</x-members.card>
@endif
<x-members.card title="Journalist?">
<p class="!m-0 text-sm">Please feel free to use the data on this page, but if you do you must cite OpenAustralia.org in the body of your articles as the source of any analysis or data you get off this site.</p>
</x-members.card>
</aside>
