@extends('layout.legacy')

@section('content')
<div class="px-4 pb-12 pt-6 md:px-8">
<p class="!mx-0">That name is not unique. Please select from the following:</p>
<ul class="!mx-0 !pl-5">
@foreach ($matches as $term)
<li><a href="{{ WEBPATH }}mp/?pid={{ $term->person_id }}">{{ $term->full_name }}, {{ $term->constituency }}</a></li>
@endforeach
</ul>
<p class="!mx-0"><a href="{{ route('representatives.index', [], false) }}">Browse all MPs</a></p>
</div>
@endsection
