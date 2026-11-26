<section aria-labelledby="page-h" class="mb-8 flex flex-wrap items-center justify-between gap-x-8 gap-y-4 rounded-lg {{ $theme->hero }} p-5 !text-white shadow-md md:p-8">
<div class="min-w-0 flex-[1_1_360px]">
<span class="inline-block rounded-full {{ $theme->chip }} px-3 py-0.5 text-xs font-bold uppercase tracking-[0.08em] !text-white">{{ $house->title() }}</span>
<h1 id="page-h" class="!m-0 !mt-2 !block !p-0 text-[clamp(26px,4vw,36px)] font-extrabold !leading-tight !text-white">{{ $heading }}</h1>
<p class="!m-0 !mt-2 text-[17px] {{ $theme->label }}">{{ $lead }}</p>
</div>
@yield('finder')
</section>
