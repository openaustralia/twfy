{{-- A mailto link to the contact address, written as entities so scrapers don't read it. The slot is the link text. --}}
@php($address = str_replace('@', '&#64;', e(CONTACTEMAIL)))
<a href="mailto:{!! $address !!}">{!! $slot->isEmpty() ? $address : $slot !!}</a>
