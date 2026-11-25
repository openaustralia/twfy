@use('OpenAustralia\TWFY\Parliament\MemberUrls')
<nav aria-label="Explore {{ $profile->name() }}'s record" class="mb-8">
<ul class="!m-0 grid list-none grid-cols-[repeat(auto-fit,minmax(240px,1fr))] gap-4 !p-0">
@if (defined('DISPLAY_VOTING_DATA') && DISPLAY_VOTING_DATA)
<li class="!m-0 flex">
<x-members.tile :href="MemberUrls::theyVoteForYou($person->latest()->member_id)" title="They Vote For You" note="Key issues, on a partner site" :theme="$theme" icon="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6M15 3h6v6M10 14L21 3" />
</li>
@endif
<li class="!m-0 flex">
<x-members.tile href="#numbers" title="Numbers" note="Activity statistics" :theme="$theme" icon="M18 20V10M12 20V4M6 20v-6" />
</li>
@if ($profile->canBeFollowed())
<li class="!m-0 flex">
<x-members.tile href="#register" title="Register of Interests" :note="$facts->get('aph_interests_url') ? 'Their declared interests' : 'Scan not yet available'" :theme="$theme" icon="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8zM14 2v6h6M16 13H8M16 17H8M10 9H8" />
</li>
@endif
</ul>
</nav>
