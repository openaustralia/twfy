@extends('members.list', [
    'page' => 'mps',
    'route' => 'representatives.index',
    'heading' => 'All Members of the House of Representatives',
    'area' => 'Constituency',
])

@section('intro')
@if ($yours)
@php($name = $yours->short_name)
<p class="!mx-0">Find out more about <a href="{{ WEBPATH }}mp/"><strong>{{ $name }}, your {{ $yours->left_house->year === 9999 ? '' : 'former' }} Representative</strong></a>, including their <a href="{{ WEBPATH }}mp/?recent=1">most recent speeches</a>.</p>
<p class="!mx-0">If {{ $name }} is not your Representative, <a href="{{ WEBPATH }}user/changepc/">provide a new postcode</a>.</p>
@else
<p class="!mx-0">Find out who <a href="{{ WEBPATH }}mp/">your Representative</a> is. All you need is a postcode.</p>
@endif
@endsection
