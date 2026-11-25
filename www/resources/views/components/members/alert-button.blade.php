{{-- Asks for an email whenever someone speaks. --}}
@use('OpenAustralia\TWFY\Parliament\MemberUrls')
@props(['person', 'name', 'theme'])
<a href="{{ MemberUrls::alert($person) }}" class="box-border inline-flex min-h-[44px] items-center gap-2.5 rounded {{ $theme->button }} px-5 py-2.5 text-base font-semibold !text-white !no-underline shadow">
<x-members.icon path="M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2zM22 6l-10 7L2 6" />
Email me whenever {{ $name }} speaks
</a>
