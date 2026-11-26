@if ($profile->canBeFollowed())
@php($link = $theme->link . ' !underline')
<x-members.section id="register">
<x-slot:title>Register of Interests <small class="text-sm font-normal">(<a href="{{ WEBPATH }}help/#regmem" class="{{ $link }}">What's this?</a>)</small></x-slot:title>
<div class="rounded-lg border border-solid border-slate-300 bg-white p-5 text-base !text-slate-700">
@if ($facts->get('aph_interests_url'))
<p class="!m-0"><a href="{{ $facts->get('aph_interests_url') }}" class="{{ $link }}">{{ $profile->name() }}'s latest interest statement <img alt="PDF" src="{{ IMAGEPATH }}pdficon_small.gif"></a>
@if ($facts->date('aph_interests_last_updated'))
<small>Last updated: {{ $facts->date('aph_interests_last_updated') }}</small>
@endif
</p>
@else
<p class="!m-0">Scan of {{ $profile->name() }}'s entry is not yet available</p>
@endif
</div>
</x-members.section>
@endif
