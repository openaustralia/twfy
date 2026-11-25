<form role="search" action="{{ WEBPATH }}search/" method="get" class="rounded-2xl bg-slate-50 p-5">
<label for="speech-search" class="mb-2.5 block text-lg font-bold !text-slate-900">Search {{ $profile->heading() }}’s speeches</label>
<div class="flex flex-wrap gap-2">
<input id="speech-search" type="search" name="s" maxlength="200" class="box-border min-h-[44px] min-w-0 flex-[1_1_220px] rounded border border-solid border-slate-400 bg-white px-4 py-2.5 text-base !text-slate-900">
<input type="hidden" name="pid" value="{{ $person->id }}">
<button type="submit" class="inline-flex min-h-[44px] cursor-pointer items-center gap-2.5 rounded border-0 {{ $theme->button }} px-6 py-2.5 text-base font-semibold !text-white shadow">
<x-members.icon path="M11 19a8 8 0 1 0 0-16 8 8 0 0 0 0 16zM21 21l-4.35-4.35" />
Search
</button>
</div>
</form>
