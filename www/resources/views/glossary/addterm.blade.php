<h1 class="!mb-6 !mt-0 !block !leading-tight text-3xl font-semibold !text-slate-900">Add a glossary item</h1>

<section class="mb-8 rounded-2xl bg-slate-50 p-6 md:p-8">
<h2 class="!mx-0 !mb-3 !mt-0 text-xl font-semibold !text-slate-900">How this works</h2>
<ol class="m-0 list-none p-0 text-lg text-slate-700">
<li class="!mx-0 !mb-2 !mt-0"><strong>Step 1:</strong> Search for a phrase, and check how many times it appears in Hansard.</li>
<li class="!mx-0 !mb-2 !mt-0"><strong>Step 2:</strong> Write a definition for it.</li>
<li class="!mx-0 !mb-2 !mt-0"><strong>Step 3:</strong> A moderator checks your suggestion, and it should appear on the site within a day or two.</li>
</ol>
<p class="!mb-0 mt-4 text-slate-600">You need to be signed in to suggest a term.</p>
</section>

@if ($notice)
<p role="alert" class="mb-6 rounded-lg bg-rose-50 px-4 py-3 text-lg text-rose-800">{{ $notice }}</p>
@endif

@if ($thanks !== null)
<section class="mb-8 rounded-2xl bg-teal-50 p-6 md:p-8">
<h2 class="!mx-0 !mb-3 !mt-0 text-xl font-semibold !text-teal-900">Thank you for your help</h2>
<p class="mb-3 text-lg text-slate-800">Your definition for <strong>&ldquo;{{ $thanks }}&rdquo;</strong> has been submitted and is waiting for a moderator. If all is well, it should appear on the site within the next day or so.</p>
<p class="!mb-0 text-lg text-slate-800">You can browse the existing glossary below.</p>
</section>
@include('glossary.letters')
@else

@if ($preview)
<section class="mb-8">
<h2 class="!mx-0 !mb-3 !mt-0 text-xl font-semibold !text-slate-900">Your entry should look something like this</h2>
<article class="rounded-2xl bg-white p-6 shadow-md md:p-8">
<h3 class="!mx-0 !mb-3 !mt-0 text-2xl font-semibold !text-slate-900">{{ $preview['title'] }}</h3>
{{-- Unescaped: PHP filtered the definition down to a few tags and linked the terms in it. --}}
<div class="glossary-body text-lg text-slate-800 [&_a]:!text-teal-700">{!! $preview['body_html'] !!}</div>
</article>
</section>
@endif

@if ($matches)
<section class="mb-8">
<h2 class="!mx-0 !mb-3 !mt-0 text-xl font-semibold !text-slate-900">We already have {{ count($matches) === 1 ? 'a definition' : 'some definitions' }} for that</h2>
<ul class="m-0 list-none divide-y divide-slate-200 border-0 border-y border-solid border-slate-200 p-0">
@foreach ($matches as $match)
<li class="!m-0"><a href="{{ $match['url'] }}" class="block py-3 text-lg font-semibold !text-teal-700 !no-underline hover:!underline">{{ $match['title'] }}</a></li>
@endforeach
</ul>
</section>
@endif

@if ($form)
<section class="mb-8">
@if ($count > 0)
<p class="mb-4 text-lg text-slate-700">We found <strong>{{ number_format($count) }}</strong> {{ $count === 1 ? 'occurrence' : 'occurrences' }} of <strong>&ldquo;{{ $form['phrase'] }}&rdquo;</strong> in Hansard. Have a look at the excerpts below, to make sure your definition will make sense in context.</p>
@endif
<h2 class="!mx-0 !mb-3 !mt-0 text-xl font-semibold !text-slate-900">Add a definition for &ldquo;{{ $form['phrase'] }}&rdquo;</h2>
<form action="{{ $search_url }}" method="post" class="flex flex-col gap-3">
<input type="hidden" name="g" value="{{ $form['phrase'] }}">
<input type="hidden" name="token" value="{{ $token }}">
<label for="definition" class="sr-only">Definition</label>
<textarea name="definition" id="definition" rows="8" class="!m-0 box-border w-full rounded border border-solid border-slate-300 px-4 py-3 text-base text-slate-900 shadow-sm">{{ $form['body'] }}</textarea>
<p class="!m-0 text-sm text-slate-600">Use &lt;strong&gt; for bold text and &lt;em&gt; for italics. Addresses starting with http:// or https:// become links automatically.</p>
<div class="flex flex-wrap gap-3">
<button type="submit" name="previewterm" value="Preview" class="rounded border-0 bg-slate-200 px-6 py-3 font-semibold text-slate-900 hover:bg-slate-300">Preview</button>
<button type="submit" name="submitterm" value="Post" class="rounded border-0 bg-teal-700 px-6 py-3 font-semibold text-white shadow-sm hover:bg-teal-800">Post</button>
</div>
</form>
<p class="mb-0 mt-4"><a href="{{ $search_url }}" class="!text-teal-700 hover:!underline">Search for a different phrase</a></p>
</section>
@if ($excerpts_html !== '')
<section class="mb-8">
<h2 class="!mx-0 !mb-3 !mt-0 text-xl font-semibold !text-slate-900">Excerpts from Hansard</h2>
{{-- Unescaped: the Hansard search results template draws these, from the parser's data. --}}
<div class="text-base text-slate-800">{!! $excerpts_html !!}</div>
</section>
@endif
@else

<section class="mb-8">
<h2 class="!mx-0 !mb-3 !mt-0 text-xl font-semibold !text-slate-900">Step 1: Search for a phrase</h2>
<p class="mb-4 text-lg text-slate-700">Seen a piece of jargon or an external reference? Adding it to the glossary links it everywhere a member says it.</p>
<form action="{{ $search_url }}" method="get" class="flex flex-col gap-3 sm:flex-row">
@if (!$signed_in)
<input type="hidden" name="type" value="2">
@endif
<label for="phrase" class="sr-only">Phrase to search for</label>
<input type="text" name="g" id="phrase" value="{{ $phrase }}" class="!m-0 !min-w-0 !w-full box-border rounded border border-solid border-slate-300 px-4 py-3 text-base text-slate-900 shadow-sm" placeholder="A phrase, such as &quot;point of order&quot;">
<button type="submit" class="rounded border-0 bg-teal-700 px-6 py-3 font-semibold text-white shadow-sm hover:bg-teal-800">Search</button>
</form>
<p class="mb-0 mt-4 text-slate-600">For example, a technical term or piece of jargon, such as
@foreach ($examples as $example)
<a href="{{ $example['url'] }}" class="!text-teal-700 hover:!underline">{{ $example['title'] }}</a>@if (!$loop->last) or @endif
@endforeach
.</p>
</section>

<section class="mb-8">
<h2 class="!mx-0 !mb-3 !mt-0 text-xl font-semibold !text-slate-900">Or browse the existing entries</h2>
@include('glossary.letters')
</section>
@endif
@endif
