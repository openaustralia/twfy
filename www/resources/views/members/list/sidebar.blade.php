<aside aria-label="About this list" class="mt-8 flex flex-col gap-5 lg:mt-6 lg:w-72 lg:flex-none">
<x-members.panel title="This data as a spreadsheet" soft>
<p class="!m-0 !mb-3 text-[15px] !text-slate-700">Download a CSV (Comma Separated Values) file that you can load into your favourite spreadsheet program or data-mashing software.</p>
<a href="{{ route($route, ['f' => 'csv'] + $filters, false) }}" class="box-border inline-flex min-h-[44px] items-center gap-2 rounded {{ $theme->button }} px-5 py-2.5 text-base font-semibold !text-white !no-underline shadow">
<x-members.icon path="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3" />
Download CSV
</a>
</x-members.panel>
<x-members.donate :theme="$theme" />
</aside>
