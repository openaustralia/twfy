<x-admin.heading>Hansard data in database</x-admin.heading>

<div class="mx-4">
<p><b>{{ $from }}</b> to <b>{{ $to }}</b>. Parliament was sitting for <b>{{ number_format($sittingDays) }}</b> of those days.</p>

<p>There are <b>{{ number_format($speeches) }}</b> debate speeches ({{ number_format($items) }} items including headers).</p>

<p>Per sitting day, MPs are producing <b>{{ number_format($speechesPerDay) }}</b> speeches.</p>
</div>
