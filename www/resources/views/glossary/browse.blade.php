<div class="mb-6 flex flex-wrap items-center justify-between gap-4">
<h1 class="!m-0 !block !leading-tight text-3xl font-semibold !text-slate-900">Glossary index</h1>
<a href="{{ $add_url }}" class="inline-block rounded bg-teal-700 px-6 py-3 font-semibold !text-white !no-underline shadow-sm hover:bg-teal-800">Suggest a term</a>
</div>
@include('glossary.about')
@include('glossary.letters')
@if ($terms)
<ul class="!m-0 !list-disc !pl-6 marker:text-teal-700">
@foreach ($terms as $term)
<li class="!mx-0 !mb-1 !mt-0 !pl-1"><a href="{{ $term['url'] }}" class="block py-1.5 text-lg font-semibold !text-teal-700 !no-underline hover:!underline">{{ $term['title'] }}</a></li>
@endforeach
</ul>
@else
<p class="text-lg text-slate-600">No terms start with {{ $letter }} yet.</p>
@endif
