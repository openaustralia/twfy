@extends('layout.legacy')

@section('content')
<x-account.page>
<a href="{{ route('account.show', [], false) }}" class="inline-flex min-h-[44px] items-center gap-1.5 text-[15px] font-semibold">
<x-account.icon path="M15 18l-6-6 6-6" size="16" />
Back to your details
</a>
<h1 class="!m-0 !mb-2 !mt-2 !block !p-0 text-[clamp(28px,4vw,36px)] font-bold !leading-tight text-slate-900">Edit your details</h1>
<p class="!m-0 !mb-6 max-w-[640px] text-lg leading-relaxed text-slate-600">Change your name, email address, password and email settings. Fields marked optional can be left blank.</p>
@if ($errors->any())
@php $fields = $errors->keys(); @endphp
<div role="alert" aria-labelledby="error-summary-title" class="mb-6 box-border max-w-[720px] rounded-xl border-2 border-solid border-rose-700 bg-rose-50 px-5 py-4">
<h2 id="error-summary-title" class="!m-0 !mb-2 !block !p-0 text-lg font-bold text-slate-900">Please fix {{ count($fields) === 1 ? 'this problem' : 'these ' . count($fields) . ' problems' }}</h2>
<ul class="!m-0 !pl-5 text-[15px] leading-loose">
@foreach ($fields as $name)
<li><a href="#{{ $name }}" class="font-semibold !text-rose-800">{{ $errors->first($name) }}</a></li>
@endforeach
</ul>
</div>
@endif
<x-account.status />
<form method="post" action="{{ route('account.update', [], false) }}" novalidate aria-label="Edit your details" class="flex max-w-[800px] flex-col gap-8 rounded-2xl bg-white p-5 shadow-lg md:p-8">
@csrf
@method('PUT')
<fieldset class="m-0 flex min-w-0 flex-col gap-5 border-0 p-0">
<legend class="mb-4 p-0 text-xl font-bold text-slate-900">About you</legend>
<p class="!m-0 text-[15px] text-slate-700">Enter a first name, a last name, or both.</p>
<div class="flex flex-wrap gap-5">
<x-account.field name="first_name" label="First name" :value="old('first_name', $user->firstname)" autocomplete="given-name" />
<x-account.field name="last_name" label="Last name" :value="old('last_name', $user->lastname)" autocomplete="family-name" />
</div>
<p class="!m-0 text-[15px] text-slate-700">Account type: <strong class="text-slate-900">{{ $user->status }}</strong>. Only an administrator can change this.</p>
</fieldset>

<fieldset class="m-0 flex min-w-0 flex-col gap-5 border-0 border-t border-solid border-slate-200 p-0 pt-6">
<legend class="float-left mb-4 w-full p-0 text-xl font-bold text-slate-900">Email</legend>
<div class="clear-both">
<x-account.field name="email" label="Email address" type="email" :value="old('email', $user->email)" autocomplete="email" hint="We send your alerts here. If you change it, we'll email a link to the new address first, to check it's yours." />
</div>
@if ($user->pending_email)
<div id="pending-email" class="rounded-xl border border-solid border-slate-300 bg-slate-50 px-5 py-4">
<p class="!m-0 text-base font-bold text-slate-900">Waiting for you to confirm your new email address</p>
<p class="!m-0 !mt-1.5 text-[15px] leading-relaxed text-slate-700 [overflow-wrap:anywhere]">We've emailed a link to <strong>{{ $user->pending_email }}</strong>. Your email address changes when you follow it. Until then, we'll keep sending your alerts to <strong>{{ $user->email }}</strong>.</p>
<div class="mt-3 flex flex-wrap gap-3">
<button type="submit" form="resend-email" class="box-border inline-flex min-h-[44px] cursor-pointer items-center gap-2 rounded border border-solid border-slate-500 bg-white px-5 py-2.5 text-base font-semibold text-slate-900 hover:bg-slate-50">
<x-account.icon path="M21 12a9 9 0 1 1-3-6.7L21 8M21 3v5h-5" />
Send the link again
</button>
<button type="submit" form="cancel-email" class="box-border inline-flex min-h-[44px] cursor-pointer items-center gap-2 rounded border border-solid border-slate-500 bg-white px-5 py-2.5 text-base font-semibold text-slate-900 hover:bg-slate-50">
<x-account.icon path="M18 6 6 18M6 6l12 12" />
Cancel this change
</button>
</div>
</div>
@endif
</fieldset>

<fieldset class="m-0 flex min-w-0 flex-col gap-5 border-0 border-t border-solid border-slate-200 p-0 pt-6">
<legend class="float-left mb-1 w-full p-0 text-xl font-bold text-slate-900">Change your password <span class="text-base font-normal text-slate-600">(optional)</span></legend>
<p class="clear-both !m-0 text-[15px] leading-relaxed text-slate-700">Leave these blank to keep your current password. You don't need your current password to change it, and changing it signs you out of other browsers.</p>
<div class="flex flex-wrap gap-5">
<x-account.field name="password" label="New password" type="password" autocomplete="new-password" :hint="'At least ' . \OpenAustralia\TWFY\Models\User::PASSWORD_MIN . ' characters.'" />
<x-account.field name="password_confirmation" label="Type it again" type="password" autocomplete="new-password" hint="So we know it's right." />
</div>
</fieldset>

<fieldset class="m-0 min-w-0 border-0 border-t border-solid border-slate-200 p-0 pt-6">
<legend class="float-left mb-1 w-full p-0 text-xl font-bold text-slate-900">News from us</legend>
<p class="clear-both !m-0 !mb-2 text-[15px] leading-relaxed text-slate-700">Do you want occasional emails about what's new on OpenAustralia.org.au? These are separate from your alerts.</p>
@error('news')
<p id="news-error" class="!m-0 mb-1 text-[15px] font-semibold text-rose-700"><span class="sr-only">Error: </span>{{ $message }}</p>
@enderror
<div class="flex flex-wrap gap-x-6 gap-y-2">
<label class="inline-flex min-h-[44px] cursor-pointer items-center gap-2.5 text-base text-slate-900"><input type="radio" name="news" value="1" @checked(old('news', $user->optin ? '1' : '0') === '1') class="m-0 h-[22px] w-[22px] accent-teal-700">Yes, email me</label>
<label class="inline-flex min-h-[44px] cursor-pointer items-center gap-2.5 text-base text-slate-900"><input type="radio" name="news" value="0" @checked(old('news', $user->optin ? '1' : '0') === '0') class="m-0 h-[22px] w-[22px] accent-teal-700">No thanks</label>
</div>
</fieldset>

<div class="border-0 border-t border-solid border-slate-200 pt-6">
<p class="!m-0 !mb-4 text-[15px] text-slate-700">By saving, you agree to our <a href="{{ WEBPATH }}houserules/">house rules</a>.</p>
<div class="flex flex-wrap items-center gap-x-5 gap-y-3">
<x-account.button variant="primary">
<x-account.icon path="M20 6 9 17l-5-5" />
Save changes
</x-account.button>
<a href="{{ route('account.show', [], false) }}" class="inline-flex min-h-[44px] items-center gap-1.5 text-base font-semibold">
<x-account.icon path="M18 6 6 18M6 6l12 12" />
Cancel
</a>
</div>
</div>
</form>
@if ($user->pending_email)
<form id="resend-email" method="post" action="{{ route('account.email.resend', [], false) }}">@csrf</form>
<form id="cancel-email" method="post" action="{{ route('account.email.cancel', [], false) }}">@csrf @method('DELETE')</form>
@endif
</x-account.page>
@endsection
