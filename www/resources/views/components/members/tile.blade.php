{{-- A quick link on a profile. $icon is the path of a 24 by 24 line icon. --}}
@props(['href', 'title', 'note', 'icon', 'theme'])
<li class="!m-0 flex">
<a href="{{ $href }}" class="box-border flex min-h-[44px] w-full items-center gap-3.5 rounded-lg border border-solid border-slate-300 bg-white p-4 !text-slate-900 !no-underline shadow-sm hover:border-slate-500">
<span aria-hidden="true" class="flex h-11 w-11 flex-none items-center justify-center rounded-full {{ $theme->icon }}"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $icon }}"></path></svg></span>
<span><span class="block text-[17px] font-bold">{{ $title }}</span><span class="block text-sm !text-slate-600">{{ $note }}</span></span>
</a>
</li>
