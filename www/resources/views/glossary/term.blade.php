<h1 class="!m-0 mb-6 text-3xl font-semibold !text-slate-900">{{ $term['title'] }}</h1>
@include('glossary.letters')
<article class="rounded-2xl bg-white p-6 shadow-md md:p-8">
{{-- The body is the only HTML printed unescaped: GlossaryController filters it down to a few tags and links the terms in it. --}}
<div class="glossary-body text-lg text-slate-800 [&_a]:!text-teal-700">{!! $term['body_html'] !!}</div>
<p class="mb-0 mt-6 text-sm text-slate-600">Contributed by <a href="{{ $term['contributor_url'] }}" class="!text-teal-700 hover:!underline">{{ $term['contributor'] }}</a></p>
<p class="mb-0 mt-4"><a href="{{ $term['search_url'] }}" class="!text-teal-700 hover:!underline">Search Hansard for &ldquo;{{ $term['title'] }}&rdquo;</a></p>
</article>
<nav aria-label="Other terms" class="mt-6 flex flex-col gap-3 text-sm font-semibold sm:flex-row sm:justify-between">
<a href="{{ $previous['url'] }}" class="!text-teal-700 hover:!underline">&larr; {{ $previous['title'] }}</a>
<a href="{{ $next['url'] }}" class="!text-teal-700 hover:!underline sm:text-right">{{ $next['title'] }} &rarr;</a>
</nav>
<p class="mt-8 text-slate-700">Think you know a phrase that should be here? Help us improve the site by <a href="{{ $add_url }}" class="!text-teal-700 hover:!underline">adding it</a>.</p>
