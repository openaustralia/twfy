@extends('layout.legacy')

@section('content')
<div class="px-4 pb-12 pt-6 md:px-8">
@if ($error)
<p class="!mx-0 font-semibold !text-red-700" role="alert">{{ $error }}</p>
@endif
@include('members.postcode-form')
</div>
@endsection
