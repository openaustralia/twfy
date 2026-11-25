@use('Illuminate\Support\Str')
@use('OpenAustralia\TWFY\Parliament\MemberUrls')
@php($link = $theme->link . ' !underline')
<x-members.section id="numbers" title="Numbers">
<p class="!m-0 !mb-4 text-sm !text-slate-600">Please note that numbers do not measure quality. Also, {{ $memberTitle }}s may do other things not currently covered by this site. (<a href="{{ WEBPATH }}help/#numbers" class="{{ $link }}">More about this</a>)</p>
<ul class="!m-0 grid list-none grid-cols-[repeat(auto-fit,minmax(min(300px,100%),1fr))] gap-4 !p-0">
@foreach ($statistics->debates() as $debate)
<x-members.statistic :figure="number_format($debate['count'])" :standing="$debate['standing']" :theme="$theme">
@if ($debate['count'])
<a href="{{ MemberUrls::speeches($person->id) }}" class="{{ $link }}">{{ Str::plural('debate', $debate['count']) }} spoken in</a>
@else
debates spoken in
@endif
in the last year
</x-members.statistic>
@endforeach
@if ($statistics->readingAge())
<x-members.statistic :figure="$statistics->readingAge()" :theme="$theme">
years old, on average, can understand this {{ $memberTitle }}’s speeches, going by the <a href="https://en.wikipedia.org/wiki/Flesch-Kincaid_Readability_Test" class="{{ $link }}">Flesch-Kincaid Grade Level</a> score
</x-members.statistic>
@endif
@php($followers = $statistics->followers())
<x-members.statistic :figure="number_format($followers)" :theme="$theme">
{{ $followers === 1 ? 'person is' : 'people are' }} tracking whenever this {{ $memberTitle }} speaks
@if ($profile->canBeFollowed())
<x-slot:action>
<x-members.alert-button :person="$person->id" :name="$profile->name()" :theme="$theme" />
</x-slot:action>
@endif
</x-members.statistic>
@foreach ($statistics->alliterations() as $alliteration)
<x-members.statistic :figure="number_format($alliteration['count'])" :standing="$alliteration['standing']" :theme="$theme">
three-word alliterative {{ Str::plural('phrase', $alliteration['count']) }} used in debates, such as “she sells seashells”. <a href="{{ WEBPATH }}help/#numbers" class="{{ $link }}">Why is this here?</a>
</x-members.statistic>
@endforeach
</ul>
</x-members.section>
