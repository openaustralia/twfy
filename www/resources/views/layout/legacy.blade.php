{{-- A page between the legacy header and footer. $page is the legacy page key; $metadata is optional. --}}
@inject('layout', 'OpenAustralia\TWFY\Http\LegacyLayout')
{!! $layout->top($page, $metadata ?? []) !!}
@yield('content')
{!! $layout->bottom() !!}
