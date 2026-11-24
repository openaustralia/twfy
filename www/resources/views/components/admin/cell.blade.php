@props(['numeric' => false])
<td {{ $attributes->class(['px-3 py-2', 'text-right tabular-nums' => $numeric]) }}>{{ $slot }}</td>
