{{-- A white box on a page. --}}
<section {{ $attributes->merge(['class' => 'min-w-0 rounded-2xl bg-white p-5 shadow-md md:p-8']) }}>
{{ $slot }}
</section>
