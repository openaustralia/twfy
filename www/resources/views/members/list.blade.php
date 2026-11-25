@extends('layout.legacy')

@section('content')
<div class="px-4 pb-12 pt-6 md:px-8 lg:flex lg:items-start lg:gap-8">
<div class="min-w-0 flex-1">
<h1 class="!m-0 !mb-4 !block !leading-tight text-3xl font-semibold !text-slate-900">{{ $heading }}</h1>
@yield('intro')
@include('members.table')
</div>
@include('members.sidebar')
</div>
@endsection
