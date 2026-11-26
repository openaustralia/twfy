@extends('layout.legacy')

@section('content')
<div class="px-4 pb-12 pt-6 md:px-8">
<p class="!mx-0">There are several electoral divisions within your postcode. Please select from the following:</p>
<ul class="!mx-0 !pl-5">
@foreach ($electorates as $electorate => $member)
<li><a href="{{ WEBPATH }}mp/?{{ http_build_query(['pc' => $postcode, 'c' => $electorate]) }}">{{ $member ? $member->full_name . ', ' . $electorate : $electorate }}</a></li>
@endforeach
</ul>
<p class="!mx-0 !mb-8"><a href="{{ route('representatives.index', [], false) }}">Browse all MPs</a></p>
@include('members.postcode-form')
</div>
@endsection
