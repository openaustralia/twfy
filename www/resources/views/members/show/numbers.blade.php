@use('OpenAustralia\TWFY\Parliament\MemberUrls')
@php($link = $theme->link . ' !underline')
<x-members.section id="numbers" title="Numbers">
<div class="rounded-lg border border-solid border-slate-300 bg-white p-5 text-base !text-slate-700">
<p class="!m-0 !mb-3"><em>Please note that numbers do not measure quality. Also, {{ $memberTitle }}s may do other things not currently covered by this site.</em> (<a href="{{ WEBPATH }}help/#numbers" class="{{ $link }}">More about this</a>)</p>
<ul class="!m-0 !pl-5">
@foreach ($statistics->debates() as $debate)
<li>Has spoken in
@if ($debate['count'])
<a href="{{ MemberUrls::speeches($person->id) }}" class="{{ $link }}"><strong>{{ $debate['figure'] }}</strong></a>
@else
<strong>{{ $debate['figure'] }}</strong>
@endif
in the last year{{ $debate['standing'] ? ' — ' . $debate['standing'] : '' }}.</li>
@endforeach
@if ($statistics->readingAge())
<li>This Representative's speeches are understandable to an average <strong>{{ $statistics->readingAge() }}</strong> year old, going by the <a href="https://en.wikipedia.org/wiki/Flesch-Kincaid_Readability_Test" class="{{ $link }}">Flesch-Kincaid Grade Level</a> score.</li>
@endif
@php($followers = $statistics->followers())
<li><strong>{{ $followers }}</strong> {{ $followers === 1 ? 'person is' : 'people are' }} tracking whenever this {{ $memberTitle }} speaks
@if ($profile->canBeFollowed())
— <a href="{{ MemberUrls::alert($person->id) }}" class="{{ $link }}">email me whenever {{ $profile->name() }} speaks</a>.</li>
@else
.</li>
@endif
@foreach ($statistics->alliterations() as $alliteration)
<li>Has used three-word alliterative phrases (e.g. "she sells seashells") <strong>{{ $alliteration['figure'] }}</strong> in debates{{ $alliteration['standing'] ? ' — ' . $alliteration['standing'] : '' }}. <small>(<a href="{{ WEBPATH }}help/#numbers" class="{{ $link }}">Why is this here?</a>)</small></li>
@endforeach
</ul>
</div>
</x-members.section>
