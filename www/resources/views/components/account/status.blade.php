{{-- What the last request did. An output element is a status message to screen readers, who hear it when it appears. --}}
@if (session('status'))
<output class="block !mx-0 !mb-6 max-w-[720px] rounded-xl border border-solid border-teal-200 bg-teal-50 px-4 py-3 text-[15px] leading-relaxed text-slate-900 [overflow-wrap:anywhere]">{{ session('status') }}</output>
@endif
