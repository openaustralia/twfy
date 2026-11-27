@extends('layout.legacy')

@section('content')
@php
$url = 'https://' . DOMAIN . WEBPATH;
$code = view('help.partials.search-box-embed', ['url' => $url])->render();
@endphp
<x-help.page title="Link to us">
<p><strong>Link Policy:</strong> No person or organisation needs permission to link to us. Be our guest. You can deep
    link to any page you like, from any page, at any time, without asking.</p>

<p>Alternatively, if you would like to place an OpenAustralia search box on your site, like this one&hellip;</p>

{{-- A picture of the box, not a working one: it can't be tabbed to or read as a second search form. --}}
<figure class="!m-0">
<div inert>{!! $code !!}</div>
<figcaption class="sr-only">The box has the OpenAustralia name, a postcode field to find a Representative, and a field to search Parliament.</figcaption>
</figure>

<p><label for="embed-code">Cut and paste the code below into your webpage:</label></p>
<textarea id="embed-code" readonly spellcheck="false" rows="14" class="!box-border block h-80 w-full rounded border border-solid border-slate-500 bg-white p-3 font-mono text-sm leading-relaxed text-slate-900">{{ $code }}</textarea>

<p>Please retain the link to <a href="{{ $url }}">OpenAustralia.org</a> (Google points mean prizes). Any
    questions, just drop us a line at: <a href="{{ $url }}contact/">{{ $url }}contact/</a>
</p>
</x-help.page>
@endsection
