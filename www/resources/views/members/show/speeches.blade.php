@use('OpenAustralia\TWFY\Parliament\MemberUrls')
<x-members.section id="hansard" title="Most recent appearances in parliament">
@if ($speeches === null)
<p class="!mx-0">Recent appearances are unavailable right now.</p>
@elseif (!$speeches)
<p class="!mx-0">No data to display.</p>
@else
<dl class="!m-0">
@foreach ($speeches as $speech)
{{-- The title and extract are HTML from the database and the search highlighter. --}}
<dt><a href="{{ $speech['url'] }}"><strong>{!! $speech['title'] !!}</strong></a> <small>({{ $speech['date'] }})</small></dt>
<dd class="!mx-0 !mb-3"><p class="!m-0">“{!! $speech['extract'] !!}”</p></dd>
@endforeach
</dl>
@endif
<p class="!mx-0"><a href="{{ MemberUrls::speeches($person->id) }}#n4">More of {{ $profile->name() }}'s recent appearances</a></p>
@if ($feed)
<p class="!mx-0"><a href="{{ WEBPATH . $feed }}" title="XML version of this person's recent appearances">RSS feed</a> (<a href="{{ WEBPATH }}help/#rss" title="An explanation of what RSS feeds are for">?</a>)</p>
@endif
</x-members.section>
