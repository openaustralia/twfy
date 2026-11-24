<x-admin.heading>Glossary terms</x-admin.heading>
@forelse ($terms as $term)
<h3 id="gl{{ $term['id'] }}" class="mt-6 mb-1 text-base font-semibold text-slate-900">{{ $term['title'] }}</h3>
{{-- The body is the only HTML printed unescaped: GlossaryController filters it down to a few tags and links the terms in it. --}}
<p class="glossary-body my-1 max-w-3xl">{!! $term['body_html'] !!}</p>
<p class="my-1 text-sm text-slate-600">Contributed by user <x-admin.link :href="$term['user']['url']">{{ $term['user']['name'] }}</x-admin.link> &middot; <a class="!text-rose-700 !no-underline hover:!underline" href="{{ $term['delete_url'] }}">delete</a></p>
@empty
<x-admin.note>There are no approved glossary terms.</x-admin.note>
@endforelse
