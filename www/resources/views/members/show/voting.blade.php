@if (defined('DISPLAY_VOTING_DATA') && DISPLAY_VOTING_DATA)
<section id="votingrecord" class="mt-8">
<h2 class="!m-0 !block !p-0 text-xl font-semibold !text-slate-900">See how <a href="https://theyvoteforyou.org.au/mp.php?id=uk.org.publicwhip/member/{{ $person->latest()->member_id }}">{{ $profile->name() }} voted on key issues at They Vote For You</a></h2>
</section>
@endif
