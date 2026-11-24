<x-admin.heading>Pending glossary terms</x-admin.heading>
@if ($edit)
<form action="{{ $form_url }}" method="post" class="max-w-3xl">
<input type="hidden" name="modify" value="{{ $edit['id'] }}">
<input type="hidden" name="userid" value="{{ $edit['user_id'] }}">
<p><input type="text" name="g" value="{{ $edit['title'] }}" size="80" aria-label="Term"></p>
<p><textarea name="definition" id="definition" rows="10" cols="40" aria-label="Definition">{{ $edit['body'] }}</textarea></p>
<p>Guessing the Wikipedia link:<br><x-admin.link :href="$edit['wiki_link']" target="_blank" rel="noopener">{{ $edit['wiki_link'] }}</x-admin.link></p>
<p><label>Tick here if it worked <input type="checkbox" name="wikiguess" @checked($edit['wiki_guess'])></label></p>
<p><input type="submit" name="previewterm" value="Preview" class="submit"></p>
</form>
<x-admin.note>This is what it was going to look like:</x-admin.note>
<h3 class="mt-4 mb-1 text-base font-semibold text-slate-900">{{ $edit['title'] }}</h3>
{{-- The only HTML printed unescaped: GlossaryController filters it down to a few tags and links the terms in it. --}}
<p class="glossary-body my-1 max-w-3xl">{!! $edit['preview_html'] !!}</p>
@elseif (count($pending) === 0)
<x-admin.note>Nothing pending, tap your fingers awhile.</x-admin.note>
@else
<form action="{{ $form_url }}" method="post" class="max-w-3xl">
@foreach ($pending as $item)
<div class="mb-4">
<label for="approve-{{ $item['id'] }}" class="font-semibold"><input type="checkbox" name="approve[]" value="{{ $item['id'] }}" id="approve-{{ $item['id'] }}"> {{ $item['title'] }}</label>
<p class="my-1">{{ $item['body'] }}</p>
<p class="my-1 text-sm text-slate-600">
<x-admin.link :href="$item['approve_url']">approve</x-admin.link> &middot;
<x-admin.link :href="$item['modify_url']">modify</x-admin.link> &middot;
<x-admin.link :href="$item['decline_url']">decline</x-admin.link>
<br>Submitted by <em>{{ $item['user'] }}</em>
</p>
</div>
@endforeach
<input type="submit" value="Approve checked items">
</form>
@endif
