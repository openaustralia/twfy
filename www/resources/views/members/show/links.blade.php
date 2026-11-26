<ul @if (!empty($listClass))class="{{ $listClass }}"@endif>
@foreach ($links as $each)
<li><a href="{{ $each['url'] }}"@if (!empty($class)) class="{{ $class }}"@endif>{{ $each['text'] }}</a>
@if ($each['source'])
<small>({{ $each['source'] }})</small>
@endif
</li>
@endforeach
</ul>
