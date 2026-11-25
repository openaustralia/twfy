@use('OpenAustralia\TWFY\Parliament\MemberUrls')
<x-members.section id="hansard" title="Most recent appearances in parliament">
@if ($speeches === null)
<p class="!m-0 !mb-5 text-base !text-slate-700">Recent appearances are unavailable right now.</p>
@elseif (!$speeches)
<p class="!m-0 !mb-5 text-base !text-slate-700">No data to display.</p>
@else
<ul class="!m-0 !mb-5 flex list-none flex-col gap-4 !p-0">
@foreach ($speeches as $speech)
<li class="!m-0 rounded-lg border border-solid border-slate-300 bg-white p-5">
<div class="mb-2.5 flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
{{-- The title and extract are HTML from the database and the search highlighter. --}}
<a href="{{ $speech['url'] }}" class="text-lg font-bold {{ $theme->link }} !underline">{!! $speech['title'] !!}</a>
<time datetime="{{ $speech['iso'] }}" class="text-sm !text-slate-600">{{ $speech['date'] }}</time>
</div>
<blockquote class="!m-0 max-w-[72ch] text-base leading-relaxed !text-slate-700">“{!! $speech['extract'] !!}”</blockquote>
</li>
@endforeach
</ul>
@endif
<a href="{{ MemberUrls::speeches($person->id) }}#n4" class="box-border inline-flex min-h-[44px] items-center rounded border-2 border-solid {{ $theme->outline }} px-5 py-2.5 text-base font-semibold !no-underline">More of {{ $profile->name() }}’s recent appearances &rarr;</a>
@if ($feed)
<p class="!m-0 !mt-3 text-sm"><a href="{{ WEBPATH . $feed }}" class="{{ $theme->link }} !underline" title="XML version of this person's recent appearances">RSS feed</a> (<a href="{{ WEBPATH }}help/#rss" class="{{ $theme->link }} !underline" title="An explanation of what RSS feeds are for">?</a>)</p>
@endif
</x-members.section>
