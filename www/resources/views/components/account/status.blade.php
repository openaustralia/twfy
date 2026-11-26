{{-- What the last request did, read out to screen readers when it appears. --}}
<div role="status" aria-live="polite">
@if (session('status'))
<p class="!mx-0 !mb-6 max-w-[720px] rounded-xl border border-solid border-teal-200 bg-teal-50 px-4 py-3 text-[15px] leading-relaxed text-slate-900 [overflow-wrap:anywhere]">{{ session('status') }}</p>
@endif
</div>
