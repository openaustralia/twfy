{{-- A page between the legacy header and footer. $page is the legacy page key. --}}
@inject('layout', 'OpenAustralia\TWFY\Http\LegacyLayout')
{!! $layout->top($page) !!}
@yield('content')
{!! $layout->bottom() !!}
