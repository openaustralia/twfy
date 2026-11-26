{{-- A white box on the account pages. --}}
<section {{ $attributes->merge(['class' => 'min-w-0 rounded-2xl bg-white p-5 shadow-md md:p-8']) }}>
{{ $slot }}
</section>
