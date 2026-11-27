@extends('layout.legacy')

@section('content')
<x-help.page title="Contribute to OpenAustralia.org">
<nav aria-label="Ways to help">
<ul>
    <li><a href="#nontechie">I'd like to help out, but I'm not really a Techie.</a></li>
    <li><a href="#knowsphp">I know PHP and HTML and stuff like that.</a></li>
    <li><a href="#knowsruby">I know Ruby and I can write parsers, can I help you crunch the source data?</a></li>
</ul>
</nav>

<h2 id="nontechie">I'd like to help out, but I'm not really a Techie.</h2>
<p>There's a load of things you can do. Blog about us, tell your friends about OpenAustralia, or add a glossary entry.</p>

<h2 id="knowsphp">I know PHP and HTML and stuff like that.</h2>
<p>Great, check out <a href="https://software.openaustralia.org/">web application source</a>.</p>

<h2 id="knowsruby">I know Ruby and I can write parsers, can I help you crunch the source data?</h2>
<p>You sure can. Check out the <a href="https://software.openaustralia.org/">parser source code</a> and let us
    know what you want to add.</p>
</x-help.page>
@endsection
