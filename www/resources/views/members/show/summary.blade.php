@use('OpenAustralia\TWFY\Enums\House')
@php($sitting = $person->sitsIn($house))
@php($since = $profile->since())
@php($roles = $profile->currentRoles())
@php($label = '!m-0 text-xs font-semibold uppercase tracking-[0.06em] ' . $theme->label)
<p class="hidden print:block">This data was produced by OpenAustralia from a variety of sources.</p>
<p class="!m-0 !mb-3 text-sm"><a href="{{ route($house === House::Senate ? 'senators.index' : 'representatives.index', [], false) }}" class="{{ $theme->link }} !underline">&larr; All {{ $house->memberTitle() }}s</a></p>
<section id="mp" aria-labelledby="who" class="mb-6 rounded-lg {{ $theme->hero }} p-5 !text-white shadow-md md:p-8">
@if ($history->partyNotice())
<div class="mb-6 flex items-start gap-3 rounded-md bg-white/10 px-4 py-3 text-[15px]">
<x-members.icon class="mt-0.5 {{ $theme->label }}" path="M17 1l4 4-4 4M3 11V9a4 4 0 0 1 4-4h14M7 23l-4-4 4-4M21 13v2a4 4 0 0 1-4 4H3" />
<span><strong>Changed party</strong> {{ $history->partyNotice() }}</span>
</div>
@endif
<div class="flex flex-wrap items-start gap-6">
@if ($photo)
<img class="h-[214px] w-40 flex-none rounded-xl border-4 border-solid {{ $theme->photo }} object-cover" alt="{{ $profile->name() }}" src="{{ $photo }}" width="160" height="214">
@else
<div class="box-border flex h-[214px] w-40 flex-none flex-col items-center justify-center gap-2 rounded-xl border-4 border-solid {{ $theme->photo }} p-3 text-center text-sm !text-slate-900">
<span>We're missing a photo!</span>
<a href="mailto:contact@openaustralia.org" class="font-semibold {{ $theme->link }} !underline">Email us one</a>
<small>(that you have copyright of)</small>
</div>
@endif
<div class="min-w-0 flex-[1_1_320px]">
<span class="mb-2.5 inline-block rounded-full {{ $theme->chip }} px-3 py-0.5 text-xs font-bold uppercase tracking-[0.08em] !text-white">{{ $house->title() }}</span>
<h1 id="who" class="!m-0 !block !p-0 text-[clamp(28px,4vw,40px)] font-bold !leading-tight !text-white">{{ $profile->heading() }}</h1>
@if ($profile->formerly())
<p class="!m-0 !mt-1 text-base !text-slate-300">{{ $profile->formerly() }}</p>
@endif
<dl class="!mx-0 !mb-5 !mt-4 flex flex-wrap gap-x-10 gap-y-3">
<div>
<dt class="{{ $label }}">Party</dt>
<dd class="!m-0 text-lg font-semibold">{{ $profile->party() }}</dd>
</div>
<div>
<dt class="{{ $label }}">{{ $sitting ? 'Represents' : 'Represented' }}</dt>
<dd class="!m-0 text-lg font-semibold">{{ $person->latest()->display_constituency }}</dd>
</div>
@if ($since)
<div>
<dt class="{{ $label }}">{{ $house->memberTitle() }} since</dt>
<dd class="!m-0 text-lg font-semibold">{{ $since['date'] }}
@if ($since['reason'])
<span class="font-normal !text-slate-300">({{ $since['reason'] }})</span>
@endif
</dd>
</div>
@endif
</dl>
@if ($profile->canBeFollowed())
<div class="flex flex-wrap items-center gap-x-4 gap-y-2">
<x-members.alert-button :person="$person->id" :name="$profile->name()" :theme="$theme" />
<span class="text-sm !text-slate-300">No more than once per day</span>
</div>
@endif
</div>
</div>
@if ($roles)
<div class="mt-7 border-0 border-t border-solid border-white/20 pt-5">
<h2 class="!m-0 !mb-3 !block !p-0 text-sm font-semibold uppercase tracking-[0.06em] {{ $theme->label }}">Current roles</h2>
<ul class="!m-0 grid list-none grid-cols-[repeat(auto-fit,minmax(250px,1fr))] gap-x-6 gap-y-3.5 !p-0">
@foreach ($roles as $role)
<li class="!m-0">
<div class="text-[17px] font-semibold !text-white">{{ $role['title'] }}</div>
<div class="text-sm !text-slate-300">since {{ $role['since'] }}</div>
</li>
@endforeach
</ul>
</div>
@endif
{{-- Anyone "since" can't sum up gets their histories. --}}
@if (!$since)
<div class="mt-7 grid grid-cols-[repeat(auto-fit,minmax(min(300px,100%),1fr))] gap-x-10 gap-y-6 border-0 border-t border-solid border-white/20 pt-5">
<x-members.timeline title="Party history" :entries="$history->parties()" :label="$theme->label" />
<x-members.timeline title="Electorate history" :entries="$history->seats()" :label="$theme->label" />
</div>
@endif
</section>
