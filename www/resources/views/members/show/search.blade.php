<form role="search" action="{{ WEBPATH }}search/" method="get" class="rounded-2xl bg-slate-50 p-5">
<label for="speech-search" class="mb-2.5 block text-lg font-bold !text-slate-900">Search {{ $person->latest()->display_first_name }}’s speeches</label>
<div class="flex flex-wrap gap-2">
<input id="speech-search" type="search" name="s" maxlength="200" class="box-border min-h-[44px] min-w-0 flex-[1_1_220px] rounded border border-solid border-slate-400 bg-white px-4 py-2.5 text-base !text-slate-900">
<input type="hidden" name="pid" value="{{ $person->id }}">
<button type="submit" class="min-h-[44px] cursor-pointer rounded border-0 {{ $theme->button }} px-6 py-2.5 text-base font-semibold !text-white shadow">Search</button>
</div>
</form>
