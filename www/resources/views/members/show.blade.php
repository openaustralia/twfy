@extends('layout.legacy')

@section('content')
<div class="mx-auto max-w-[1280px] px-4 pb-12 pt-4 md:px-8">
@include('members.show.summary')
@include('members.show.tiles')
<div class="lg:flex lg:items-start lg:gap-8">
<div class="min-w-0 flex-1 space-y-8">
@include('members.show.search')
@include('members.show.speeches')
@include('members.show.numbers')
@include('members.show.register')
</div>
@include('members.show.sidebar')
</div>
</div>
@endsection
