@extends('members.list', [
    'page' => 'mps',
    'route' => 'representatives.index',
    'heading' => 'All Members of the House of Representatives',
    'lead' => 'Every Representative, with their party, electorate and positions.',
    'area' => 'Electorate',
])

@section('finder')
<div class="box-border min-w-0 max-w-[460px] flex-[1_1_300px] rounded-lg border border-solid border-white/40 bg-white/10 p-4">
@if ($yours)
@php($name = $yours->short_name)
<h2 class="!m-0 !block !p-0 text-[17px] font-bold !text-white">Your {{ $yours->isSitting() ? '' : 'former ' }}Representative</h2>
<p class="!m-0 !mt-1 text-[15px] {{ $theme->label }}">Find out more about <a href="{{ WEBPATH }}mp/" class="font-semibold !text-white !underline">{{ $name }}</a>, including their <a href="{{ WEBPATH }}mp/?recent=1" class="!text-white !underline">most recent speeches</a>.</p>
<p class="!m-0 !mt-1 text-[15px] {{ $theme->label }}">If {{ $name }} is not your Representative, <a href="{{ WEBPATH }}user/changepc/" class="!text-white !underline">provide a new postcode</a>.</p>
@else
<form action="{{ WEBPATH }}mp/" method="get">
<label for="pc" class="block text-[17px] font-bold">Find your Representative</label>
<p class="!m-0 !mb-3 !mt-1 text-sm {{ $theme->label }}">Enter your postcode to see who represents you.</p>
<div class="flex flex-wrap gap-2">
<input id="pc" type="text" name="pc" maxlength="10" inputmode="numeric" autocomplete="postal-code" placeholder="Postcode" class="box-border min-h-[44px] min-w-0 flex-[1_1_120px] rounded border border-solid border-slate-400 bg-white px-3 py-2 text-base !text-slate-900">
<button type="submit" class="inline-flex min-h-[44px] cursor-pointer items-center gap-2 rounded border-0 bg-white px-5 py-2.5 text-base font-bold !text-slate-900">
<x-members.icon path="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 0 1 16 0zM12 7a3 3 0 1 0 0 6 3 3 0 0 0 0-6z" />
Find
</button>
</div>
</form>
@endif
</div>
@endsection
