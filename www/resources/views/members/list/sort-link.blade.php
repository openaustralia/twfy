{{-- A column heading that sorts the list and keeps its filters, as plain text when it already does. --}}
@if ($order === $by)
{{ $label }}
@else
<a href="{{ route($route, ['o' => $by->value] + $filters, false) }}" class="{{ $theme->link }} !underline">{{ $label }}</a>
@endif
