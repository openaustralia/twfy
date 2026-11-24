<h1 class="!m-0 mb-6 text-3xl font-semibold !text-slate-900">Glossary index</h1>
@include('glossary.letters')
@if ($terms)
<ul class="m-0 grid list-none gap-3 p-0 sm:grid-cols-2 lg:grid-cols-3">
@foreach ($terms as $term)
<li class="!m-0"><a href="{{ $term['url'] }}" class="block rounded-2xl bg-white p-4 shadow-md !text-teal-700 !no-underline hover:shadow-lg">{{ $term['title'] }}</a></li>
@endforeach
</ul>
@else
<p class="text-slate-600">No terms start with {{ $letter }} yet.</p>
@endif
<p class="mt-8 text-slate-700">Think you know a phrase that should be here? Help us improve the site by <a href="{{ $add_url }}" class="!text-teal-700 hover:!underline">adding it</a>.</p>
