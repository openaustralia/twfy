@extends('layout.legacy')

@section('content')
<x-account.page>
<p class="!m-0 text-sm font-semibold uppercase tracking-wider text-teal-700">Your account</p>
<h1 class="!m-0 !mb-8 !block !p-0 text-[clamp(28px,4vw,36px)] font-bold !leading-tight text-slate-900">Your details</h1>
<x-account.status />
<div class="flex flex-wrap items-start gap-6">
@include('account.details')
@include('account.alerts')
</div>
</x-account.page>
@endsection
