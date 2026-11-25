@if ($profile->canBeFollowed())
<section id="register" class="mt-8">
<h2 class="!m-0 !mb-3 !block !p-0 text-xl font-semibold !text-slate-900">Register of Interests <small>(<a href="{{ WEBPATH }}help/#regmem">What's this?</a>)</small></h2>
@if ($facts->get('aph_interests_url'))
<p class="!mx-0"><a href="{{ $facts->get('aph_interests_url') }}">{{ $profile->name() }}'s latest interest statement <img alt="PDF" src="{{ IMAGEPATH }}pdficon_small.gif"></a>
@if ($facts->get('aph_interests_last_updated'))
<small>Last updated: {{ format_date($facts->get('aph_interests_last_updated'), 'j M Y') }}</small>
@endif
</p>
@else
<p class="!mx-0">Scan of {{ $profile->name() }}'s entry is not yet available</p>
@endif
</section>
@endif
