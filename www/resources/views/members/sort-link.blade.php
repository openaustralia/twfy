{{-- A column heading that sorts the list, as plain text when it already does. --}}
@if ($order === $by)
{{ $label }}
@else
<a href="{{ route($route, ['o' => $by->value], false) }}">{{ $label }}</a>
@endif
