{{-- A list of alerts, each with the button that stops it or starts it again. $class styles the list. --}}
@use('OpenAustralia\TWFY\Icons')
<ul class="{{ $class }}">
@foreach ($alerts as $alert)
<li class="flex flex-wrap items-center gap-x-4 gap-y-3 border-0 border-t border-solid border-slate-200 py-3.5">
@if ($alert['photoUrl'])
<img src="{{ $alert['photoUrl'] }}" alt="" width="40" height="40" class="h-10 w-10 shrink-0 rounded-full bg-slate-200 object-cover object-top">
@else
<div aria-hidden="true" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ $alert['isKeyword'] ? 'bg-teal-100 text-teal-800' : 'bg-slate-200 text-slate-700' }} text-sm font-semibold">
@if ($alert['isKeyword'])
<x-account.icon path="M20 20l-3.5-3.5M18 11a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" size="20" />
@else
{{ $alert['initials'] }}
@endif
</div>
@endif
<div class="min-w-0 flex-[1_1_150px]">
<p class="!m-0 !mb-0.5 text-xs font-semibold uppercase tracking-wider text-slate-600">{{ $alert['isKeyword'] ? 'Word or phrase' : 'Person' }}</p>
<x-account.link href="{{ $alert['searchPath'] }}" class="text-base font-semibold">{{ $alert['isKeyword'] ? "“{$alert['label']}”" : $alert['label'] }}</x-account.link>
@if ($alert['role'])
<p class="!m-0 !mt-0.5 text-sm text-slate-600">{{ $alert['role'] }}</p>
@endif
<p class="!m-0 !mt-0.5 text-sm text-slate-600">{{ $alert['subtitle'] }}</p>
</div>
@if ($alert['chart'])
<x-account.sparkline :chart="$alert['chart']" />
@endif
@if ($alert['state'] === 'unconfirmed')
<x-account.button :href="$alert['confirmPath']">Confirm<span class="sr-only"> alerts about {{ $alert['label'] }}</span></x-account.button>
@else
<form method="post" action="{{ route($alert['state'] === 'active' ? 'account.alerts.destroy' : 'account.alerts.restore', $alert['id'], false) }}">
@csrf
@method($alert['state'] === 'active' ? 'DELETE' : 'PUT')
{{-- The same bells as the member pages' alert buttons. --}}
<x-account.button>
<x-account.icon :path="$alert['state'] === 'active' ? Icons::BELL_OFF : Icons::BELL" />
{{ $alert['state'] === 'active' ? 'Unsubscribe' : 'Subscribe again' }}<span class="sr-only"> to alerts about {{ $alert['label'] }}</span>
</x-account.button>
</form>
@endif
</li>
@endforeach
</ul>
