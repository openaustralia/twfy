{{-- A labelled text box with an optional hint and the first error for its name. --}}
@props(['name', 'label', 'value' => '', 'type' => 'text', 'hint' => null, 'autocomplete' => null, 'maxlength' => null])
@php
$error = $errors->first($name);
$describedBy = trim(($hint ? "$name-hint " : '') . ($error ? "$name-error" : ''));
@endphp
<div class="min-w-0 flex-[1_1_220px]">
<label for="{{ $name }}" class="block text-base font-bold text-slate-900">{{ $label }}</label>
@if ($hint)
<p id="{{ $name }}-hint" class="!m-0 !mt-1 text-sm text-slate-600">{{ $hint }}</p>
@endif
@if ($error)
<p id="{{ $name }}-error" class="!m-0 !mt-1 text-[15px] font-semibold text-rose-700"><span class="sr-only">Error: </span>{{ $error }}</p>
@endif
<input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ $value }}"@if ($autocomplete) autocomplete="{{ $autocomplete }}"@endif @if ($maxlength) maxlength="{{ $maxlength }}"@endif @if ($error) aria-invalid="true"@endif @if ($describedBy !== '') aria-describedby="{{ $describedBy }}"@endif
class="mt-2 box-border block min-h-[44px] w-full rounded border {{ $error ? 'border-2 border-rose-700' : 'border-slate-500' }} border-solid bg-white px-3 py-2.5 text-base text-slate-900">
</div>
