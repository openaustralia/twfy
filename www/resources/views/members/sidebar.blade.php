@php($csv = route($route, ['f' => 'csv'], false))
<aside class="mt-8 space-y-4 lg:mt-0 lg:w-64 lg:shrink-0">
<x-members.card>
<x-slot:title><a href="{{ $csv }}">This data as a spreadsheet</a></x-slot:title>
<p class="!m-0 text-sm"><a href="{{ $csv }}">Download a CSV</a> (Comma Separated Values) file that you can load into your favourite spreadsheet program or data-mashing software.</p>
</x-members.card>
@include('members.donate')
</aside>
