{{-- The admin pages as a hamburger menu at the left of the site menu. PAGE::menu() draws it for admins only. --}}
<details id="admin-menu" class="relative">
<summary aria-label="Admin menu" class="flex h-9 w-9 cursor-pointer list-none items-center justify-center rounded text-slate-200 hover:bg-white/10 hover:text-white [&::-webkit-details-marker]:hidden">
<svg width="20" height="20" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M3 5h14M3 10h14M3 15h14"/></svg>
</summary>
<nav aria-label="Admin" class="absolute left-0 z-50 mt-2 w-72 rounded-lg bg-white py-2 shadow-lg ring-1 ring-slate-200">
@foreach ($sections as $section)
@if ($section['heading'])
<p class="m-0 px-4 pb-1 pt-3 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $section['heading'] }}</p>
@endif
<ul class="!m-0 list-none !p-0">
@foreach ($section['links'] as $link)
<li class="!ml-0">
<a href="{{ $link['url'] }}" @if ($link['current']) aria-current="page" @endif class="flex items-center gap-3 px-4 py-2 text-sm !no-underline hover:bg-teal-50 {{ $link['current'] ? 'bg-slate-100 font-semibold !text-slate-900' : '!text-teal-700' }}">@if ($link['icon'])<x-admin.icon :name="$link['icon']" />@endif{{ $link['title'] }}</a>
</li>
@endforeach
</ul>
@endforeach
</nav>
</details>
<script>
(function () {
  var menu = document.getElementById('admin-menu');
  document.addEventListener('click', function (event) { if (!menu.contains(event.target)) { menu.removeAttribute('open'); } });
  document.addEventListener('keydown', function (event) { if (event.key === 'Escape') { menu.removeAttribute('open'); } });
})();
</script>
