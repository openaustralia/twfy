@php($csv = route($route, ['f' => 'csv'], false))
<aside class="mt-8 space-y-4 lg:mt-0 lg:w-64 lg:shrink-0">
<section class="rounded-lg bg-slate-100 p-4">
<h2 class="!m-0 !mb-2 !block !p-0 text-base font-semibold !text-slate-900"><a href="{{ $csv }}">This data as a spreadsheet</a></h2>
<p class="!m-0 text-sm"><a href="{{ $csv }}">Download a CSV</a> (Comma Separated Values) file that you can load into your favourite spreadsheet program or data-mashing software.</p>
</section>
<section class="rounded-lg bg-slate-100 p-4">
<h2 class="!m-0 !mb-2 !block !p-0 text-base font-semibold !text-slate-900">Did you know this site is run by a charity?</h2>
<p class="!m-0 text-sm"><a href="https://donate.oaf.org.au/"><img class="float-right ml-2 border-0" src="{{ IMAGEPATH }}donate_greenL.png" width="108" height="43" alt="Donate"></a>Your donation would enable us to continue to add new features.</p>
</section>
</aside>
