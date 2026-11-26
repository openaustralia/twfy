@extends('layout.legacy')

@section('content')
<div class="mx-auto max-w-[1280px] px-4 pb-12 pt-6 md:px-8">
@include('members.list.hero')
<div class="lg:flex lg:items-start lg:gap-8">
<section aria-labelledby="members" class="min-w-0 flex-1">
<h2 id="members" class="sr-only">{{ $house->memberTitle() }}s</h2>
@include('members.list.filters')
@include('members.list.members')
</section>
@include('members.list.sidebar')
</div>
</div>
@endsection
