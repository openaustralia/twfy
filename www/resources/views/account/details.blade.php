<x-card aria-labelledby="details-heading" class="flex-[1_1_300px]">
<div class="mb-5 flex items-center gap-4">
<div aria-hidden="true" class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-teal-100 text-lg font-bold text-teal-800">{{ mb_strtoupper(mb_substr($user->firstname, 0, 1) . mb_substr($user->lastname, 0, 1)) }}</div>
<h2 id="details-heading" class="!m-0 !block !p-0 text-xl font-bold text-slate-900">Your account</h2>
</div>
<dl class="!m-0 border-0 border-b border-solid border-slate-200">
<div class="border-0 border-t border-solid border-slate-200 py-3"><dt class="text-sm text-slate-600">Name</dt><dd class="!m-0 mt-0.5 text-base font-semibold text-slate-900">{{ $user->fullName() }}</dd></div>
<div class="border-0 border-t border-solid border-slate-200 py-3"><dt class="text-sm text-slate-600">Email</dt><dd class="!m-0 mt-0.5 text-base text-slate-900 [overflow-wrap:anywhere]">{{ $user->email }}</dd></div>
<div class="border-0 border-t border-solid border-slate-200 py-3"><dt class="text-sm text-slate-600">Account type</dt><dd class="!m-0 mt-0.5 text-base text-slate-900">{{ $user->status }}</dd></div>
@if ($user->registrationtime && $user->registrationtime->year > 2000)
<div class="border-0 border-t border-solid border-slate-200 py-3"><dt class="text-sm text-slate-600">Joined</dt><dd class="!m-0 mt-0.5 text-base text-slate-900"><time datetime="{{ $user->registrationtime->toDateString() }}">{{ $user->registrationtime->format('j F Y') }}</time></dd></div>
@endif
</dl>
@if ($user->pending_email)
<p class="!mx-0 mt-5 text-[15px] leading-relaxed text-slate-700 [overflow-wrap:anywhere]">Waiting for you to confirm your new email address, <strong>{{ $user->pending_email }}</strong>. <x-account.link href="{{ route('account.edit', [], false) }}">See what to do</x-account.link>.</p>
@endif
<div class="mt-5">
<x-account.button :href="route('account.edit', [], false)">
<x-icon path="M12 20h9M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z" size="18" />
Edit your details
</x-account.button>
</div>
</x-card>
