@php($heading = '!m-0 !mb-2 !block !p-0 text-base font-semibold !text-slate-900')
<aside class="mt-8 space-y-4 lg:mt-0 lg:w-72 lg:shrink-0">
@include('members.donate')
<section class="rounded-lg bg-slate-100 p-4">
<h2 class="{{ $heading }}">Search this person's speeches</h2>
<form action="{{ WEBPATH }}search/" method="get" class="flex gap-2">
<input name="s" aria-label="Search this person's speeches" maxlength="200" class="min-w-0 flex-1">
<input type="hidden" name="pid" value="{{ $person->id }}">
<input type="submit" class="submit" value="GO">
</form>
</section>
@if ($links)
<section class="rounded-lg bg-slate-100 p-4">
<h2 class="{{ $heading }}">More useful links for this person</h2>
<ul class="!m-0 !pl-5 text-sm">
@foreach ($links as $link)
<li><a href="{{ $link['url'] }}">{{ $link['text'] }}</a>
@if ($link['source'])
<small>({{ $link['source'] }})</small>
@endif
</li>
@endforeach
</ul>
</section>
@endif
@foreach (['Previous' => $profile->predecessors(), 'Succeeding' => $profile->successors()] as $which => $neighbours)
@if ($neighbours->isNotEmpty())
<section class="rounded-lg bg-slate-100 p-4">
<h2 class="{{ $heading }}">{{ $which }} Representatives in this constituency</h2>
<ul class="!m-0 !pl-5 text-sm">
@foreach ($neighbours as $neighbour)
<li><a href="{{ WEBPATH }}mp/?pid={{ $neighbour->person_id }}">{{ $neighbour->first_name }} {{ $neighbour->last_name }}</a></li>
@endforeach
</ul>
</section>
@endif
@endforeach
@if ($profile->pastOffices())
<section class="rounded-lg bg-slate-100 p-4">
<h2 class="{{ $heading }}">Other offices held in the past</h2>
<ul class="!m-0 !pl-5 text-sm">
@foreach ($profile->pastOffices() as $office)
<li>{{ $office }}</li>
@endforeach
</ul>
</section>
@endif
<section class="rounded-lg bg-slate-100 p-4">
<h2 class="{{ $heading }}">Journalist?</h2>
<p class="!m-0 text-sm">Please feel free to use the data on this page, but if you do you must cite OpenAustralia.org in the body of your articles as the source of any analysis or data you get off this site.</p>
</section>
</aside>
