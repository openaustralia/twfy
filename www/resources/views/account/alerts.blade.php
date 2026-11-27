@php
$active = $alerts->where('state', 'active');
$unconfirmed = $alerts->where('state', 'unconfirmed');
$unsubscribed = $alerts->where('state', 'unsubscribed');
$charted = $active->first(fn (array $alert): bool => $alert['chart'] !== null);
@endphp
<x-card aria-labelledby="alerts-heading" class="flex-[999_1_520px] !shadow-lg">
<div class="mb-2 flex flex-wrap items-start justify-between gap-4">
<div class="min-w-0 flex-[1_1_260px]">
<h2 id="alerts-heading" class="!m-0 !block !p-0 text-2xl font-bold text-slate-900">Your email alerts</h2>
<p class="!m-0 !mt-1.5 text-base leading-relaxed text-slate-600">We email you when these people speak in Parliament, or when these words come up.</p>
</div>
<x-account.button variant="primary" href="#add-alert">
<x-icon path="M12 5v14M5 12h14" size="18" />
Add an alert
</x-account.button>
</div>
@if ($suppressedSince)
<p class="!mx-0 mt-4 rounded-xl border border-solid border-amber-300 bg-amber-50 px-4 py-3 text-[15px] leading-relaxed text-slate-900">Emails to this address have been returning as undeliverable since {{ $suppressedSince->format('j F Y') }}, so your alerts are paused. To resume them, you can change the email address on your account, or, once the problem is fixed, <x-account.link href="{{ WEBPATH }}contact/">contact us</x-account.link> and we will turn them back on.</p>
@endif
@if ($active->isEmpty())
<p class="!mx-0 mt-5 text-[15px] text-slate-600">You don't have any alerts yet. Add one below.</p>
@else
<p class="!mx-0 mt-5 text-[15px] leading-relaxed text-slate-600">You have {{ $active->count() }} {{ $active->count() === 1 ? 'alert' : 'alerts' }}.@if ($charted) The line beside each person shows how many times they spoke in Hansard each month, from {{ $charted['chart']['from'] }} to {{ $charted['chart']['to'] }}.@endif</p>
<x-account.alert-list :alerts="$active" class="!m-0 !mt-2 list-none !p-0" />
@endif
@if ($unconfirmed->isNotEmpty())
<h3 class="!m-0 !mb-2 !mt-8 !block !p-0 text-base font-bold text-slate-900">Waiting for you to confirm</h3>
<x-account.alert-list :alerts="$unconfirmed" class="!m-0 list-none !p-0" />
@endif
@if ($unsubscribed->isNotEmpty())
<h3 class="!m-0 !mb-2 !mt-8 !block !p-0 text-base font-bold text-slate-900">Unsubscribed</h3>
<x-account.alert-list :alerts="$unsubscribed" class="!m-0 list-none !p-0" />
@endif
<div id="add-alert" class="mt-7 rounded-xl border border-solid border-slate-200 bg-slate-50 p-5">
<h3 class="!m-0 !mb-2 !block !p-0 text-base font-bold text-slate-900">Add another alert</h3>
<p class="!m-0 text-[15px] leading-relaxed text-slate-700">Go to a <x-account.link href="{{ route('representatives.index', [], false) }}">Representative's</x-account.link> or <x-account.link href="{{ route('senators.index', [], false) }}">Senator's</x-account.link> page, or <x-account.link href="{{ WEBPATH }}search/">search Hansard</x-account.link>. You'll see the option to turn it into an alert. You can also <x-account.link href="{{ WEBPATH }}alert/">add an alert by hand</x-account.link>.</p>
</div>
</x-card>
