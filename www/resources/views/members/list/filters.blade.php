@php($field = 'box-border min-h-[44px] w-full rounded border border-solid border-slate-400 bg-white px-3 py-2 text-base !text-slate-900')
@php($label = 'text-[13px] font-semibold !text-slate-700')
<form action="{{ route($route, [], false) }}" method="get" role="search" aria-label="Filter {{ strtolower($house->memberTitle()) }}s" class="flex flex-wrap items-end gap-x-4 gap-y-3 border-0 border-b-2 border-solid {{ $theme->rule }} pb-4">
@foreach ($orderParameters as $name => $value)
<input type="hidden" name="{{ $name }}" value="{{ $value }}">
@endforeach
<div class="flex min-w-0 flex-[2_1_220px] flex-col gap-1">
<label for="q" class="{{ $label }}">Find by name or {{ strtolower($area) }}</label>
<input id="q" type="search" name="q" value="{{ $search }}" maxlength="100" class="{{ $field }}">
</div>
<div class="flex min-w-0 flex-[1_1_170px] flex-col gap-1">
<label for="party" class="{{ $label }}">Party</label>
<select id="party" name="party" class="{{ $field }}">
<option value="">All parties</option>
@if ($party !== null && !$parties->contains($party))
<option value="{{ $party }}" selected>{{ $party }}</option>
@endif
@foreach ($parties as $each)
<option value="{{ $each }}" @selected($each === $party)>{{ $each }}</option>
@endforeach
</select>
</div>
<div class="flex flex-wrap items-center gap-x-4 gap-y-2">
<button type="submit" class="inline-flex min-h-[44px] cursor-pointer items-center gap-2.5 rounded border-0 {{ $theme->button }} px-6 py-2.5 text-base font-semibold !text-white shadow">
<x-members.icon path="M11 19a8 8 0 1 0 0-16 8 8 0 0 0 0 16zM21 21l-4.35-4.35" />
Filter
</button>
@if ($filters)
<a href="{{ route($route, $orderParameters, false) }}" class="text-base font-semibold {{ $theme->link }} !underline">Clear filters</a>
@endif
</div>
</form>
