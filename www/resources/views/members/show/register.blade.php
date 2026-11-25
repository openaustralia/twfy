@if ($profile->canBeFollowed())
<x-members.section id="register">
<x-slot:title>Register of Interests <small>(<a href="{{ WEBPATH }}help/#regmem">What's this?</a>)</small></x-slot:title>
@if ($facts->get('aph_interests_url'))
<p class="!mx-0"><a href="{{ $facts->get('aph_interests_url') }}">{{ $profile->name() }}'s latest interest statement <img alt="PDF" src="{{ IMAGEPATH }}pdficon_small.gif"></a>
@if ($facts->date('aph_interests_last_updated'))
<small>Last updated: {{ $facts->date('aph_interests_last_updated') }}</small>
@endif
</p>
@else
<p class="!mx-0">Scan of {{ $profile->name() }}'s entry is not yet available</p>
@endif
</x-members.section>
@endif
