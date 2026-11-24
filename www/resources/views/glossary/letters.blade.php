{{-- The A to Z strip. A letter is a link when some term starts with it, and marked when it is the one being shown. --}}
<nav aria-label="Glossary letters" class="mb-6">
<ul class="m-0 flex list-none flex-wrap gap-2 p-0">
@foreach ($letters as $letter)
<li class="!m-0">
@if ($letter['url'])
<a href="{{ $letter['url'] }}" @if ($letter['current']) aria-current="page" @endif class="flex h-10 w-10 items-center justify-center rounded font-semibold !no-underline {{ $letter['current'] ? 'bg-[#26343b] !text-white' : 'bg-teal-50 !text-teal-700 hover:bg-teal-100' }}">{{ $letter['letter'] }}</a>
@else
<span class="flex h-10 w-10 items-center justify-center rounded {{ $letter['current'] ? 'bg-[#26343b] text-white' : 'bg-slate-100 text-slate-400' }}">{{ $letter['letter'] }}</span>
@endif
</li>
@endforeach
</ul>
</nav>
