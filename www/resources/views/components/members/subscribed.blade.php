{{-- Says the visitor already gets email about someone, and lets them stop, as their account page does. --}}
@use('OpenAustralia\TWFY\Icons')
@props(['alert', 'name', 'theme'])
<div class="flex flex-col items-start gap-2">
<p class="!m-0 flex items-center gap-2 text-base font-semibold !text-slate-900">
<x-members.icon class="{{ $theme->link }}" path="M20 6L9 17l-5-5" />
You’re subscribed
</p>
<p class="!m-0 text-sm !text-slate-600">We’ll email you at most once a day when {{ $name }} speaks.</p>
{{-- The token authorises this, so show it to nobody but the alert's owner. --}}
<form action="{{ WEBPATH }}alert/delete/" method="post" class="!m-0">
<input type="hidden" name="t" value="{{ $alert->token }}">
<button type="submit" class="inline-flex min-h-[44px] cursor-pointer items-center gap-2.5 rounded border-2 border-solid {{ $theme->outline }} bg-white px-5 py-2 text-base font-semibold">
<x-members.icon :path="Icons::BELL_OFF" />
Unsubscribe
</button>
</form>
</div>
