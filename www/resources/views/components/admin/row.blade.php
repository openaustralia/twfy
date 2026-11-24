@props(['alert' => false])
<tr {{ $attributes->class(['border-0 border-b border-solid border-slate-200', 'bg-rose-50 hover:bg-rose-100' => $alert, 'even:bg-slate-50 hover:bg-teal-50' => !$alert]) }}>{{ $slot }}</tr>
