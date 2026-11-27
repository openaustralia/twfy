{{-- Asks for an email whenever someone speaks. --}}
@use('OpenAustralia\TWFY\Icons')
@use('OpenAustralia\TWFY\Parliament\MemberUrls')
@props(['person', 'name', 'theme'])
<a href="{{ MemberUrls::alert($person) }}" class="box-border inline-flex min-h-[44px] items-center gap-2.5 rounded {{ $theme->button }} px-5 py-2.5 text-base font-semibold !text-white !no-underline shadow">
<x-members.icon :path="Icons::BELL" />
Email me whenever {{ $name }} speaks
</a>
