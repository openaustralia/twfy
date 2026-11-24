<h1 class="!mb-6 !mt-0 text-3xl font-semibold !text-slate-900">Glossary index</h1>
@include('glossary.about')
@include('glossary.letters')
@if ($terms)
<ul class="m-0 list-none divide-y divide-slate-200 border-0 border-y border-solid border-slate-200 p-0 md:columns-2 md:gap-10">
@foreach ($terms as $term)
<li class="!m-0 break-inside-avoid"><a href="{{ $term['url'] }}" class="block py-3 text-lg font-semibold !text-teal-700 !no-underline hover:!underline">{{ $term['title'] }}</a></li>
@endforeach
</ul>
@else
<p class="text-lg text-slate-600">No terms start with {{ $letter }} yet.</p>
@endif
