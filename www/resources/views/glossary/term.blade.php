<h1 class="!mb-6 !mt-0 !flex !items-center gap-3 !leading-tight text-3xl font-semibold !text-slate-900">@include('glossary.icon', ['class' => 'h-8 w-8 shrink-0 text-teal-700'])Glossary Term</h1>
<article class="rounded-2xl bg-white p-6 shadow-md md:p-8">
<h2 class="!mx-0 !mb-3 !mt-0 text-2xl font-semibold !text-teal-700">{{ $term['title'] }}</h2>
{{-- The body is the only HTML printed unescaped: GlossaryController filters it down to a few tags and links the terms in it. --}}
<div class="glossary-body text-lg text-slate-800 [&_a]:!text-teal-700">{!! $term['body_html'] !!}</div>
{{-- The name is the copy saved with the term, as plain text with no link, so changing or taking over an account can't change what a published term says. --}}
@if ($term['contributor'] !== '')
<p class="mb-0 mt-6 text-sm text-slate-600">Contributed by {{ $term['contributor'] }}</p>
@endif
<p class="mb-0 mt-4"><a href="{{ $term['search_url'] }}" class="!text-teal-700 hover:!underline">Search Hansard for &ldquo;{{ $term['title'] }}&rdquo;</a></p>
</article>
<nav aria-label="Other terms" class="mt-6 flex flex-col gap-3 text-sm font-semibold sm:flex-row sm:justify-between">
<a href="{{ $previous['url'] }}" class="!text-teal-700 hover:!underline">&larr; {{ $previous['title'] }}</a>
<a href="{{ $next['url'] }}" class="!text-teal-700 hover:!underline sm:text-right">{{ $next['title'] }} &rarr;</a>
</nav>
<div class="mt-8">
@include('glossary.letters')
</div>
<p class="mt-8 text-slate-700">Think you know a phrase that should be here? Help us improve the site by <a href="{{ $add_url }}" class="!text-teal-700 hover:!underline">adding it</a>.</p>
