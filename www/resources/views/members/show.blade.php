@extends('layout.legacy')

@section('content')
<div class="px-4 pb-12 pt-6 md:px-8 lg:flex lg:items-start lg:gap-8">
<div class="min-w-0 flex-1">
@include('members.show.summary')
@include('members.show.voting')
@include('members.show.speeches')
@include('members.show.numbers')
@include('members.show.register')
</div>
@include('members.show.sidebar')
</div>
@endsection
