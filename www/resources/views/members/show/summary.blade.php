@use('OpenAustralia\TWFY\Parliament\MemberUrls')
<p class="hidden print:block">This data was produced by OpenAustralia from a variety of sources.</p>
<section id="mp">
<x-members.title>
@if ($feed)
<a href="{{ WEBPATH . $feed }}" class="float-right"><img src="{{ WEBPATH }}images/rss.gif" alt="RSS feed" class="border-0"></a>
@endif
{{ $profile->heading() }}
</x-members.title>
<div class="sm:flex sm:items-start sm:gap-6">
@if ($photo)
<img class="portrait mb-4 shrink-0" alt="Photo of {{ $profile->name() }}" src="{{ $photo }}" @if ($photoSize === 'S') height="118" @endif>
@else
<div class="mb-4 w-24 shrink-0 rounded border border-solid border-slate-300 p-2 text-center text-sm">We're missing a photo! <a href="mailto:contact@openaustralia.org">Email us one</a> <small>(that you have copyright of)</small></div>
@endif
<ul class="!m-0 min-w-0 !pl-5">
@foreach ($profile->roles() as $role)
<li><strong>{{ $role }}</strong></li>
@endforeach
@if ($profile->partyChanges())
<li>{{ $profile->partyChanges() }}</li>
@endif
@if ($profile->currentOffices())
<li>
@foreach ($profile->currentOffices() as $office)
<div>{{ $office }}</div>
@endforeach
</li>
@endif
@foreach ($profile->history() as $event)
<li><strong>{{ $event['text'] }}</strong>{{ $event['reason'] ? ' — ' . $event['reason'] : '' }}</li>
@endforeach
@if ($profile->canBeFollowed())
<li><a href="{{ MemberUrls::alert($person->id) }}"><strong>Email me whenever {{ $profile->name() }} speaks</strong></a> (no more than once per day)</li>
@endif
</ul>
</div>
<ul class="jumpers !mx-0 !mt-4 !pl-5">
@if (defined('DISPLAY_VOTING_DATA') && DISPLAY_VOTING_DATA)
<li><a href="#votingrecord">Voting record</a></li>
@endif
<li><a href="#numbers">Numbers</a></li>
@if ($profile->canBeFollowed())
<li><a href="#register">Register of Interests</a></li>
@endif
</ul>
</section>
