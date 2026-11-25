@if (defined('DISPLAY_VOTING_DATA') && DISPLAY_VOTING_DATA)
<x-members.section id="votingrecord">
<x-slot:title>See how <a href="https://theyvoteforyou.org.au/mp.php?id=uk.org.publicwhip/member/{{ $person->latest()->member_id }}">{{ $profile->name() }} voted on key issues at They Vote For You</a></x-slot:title>
</x-members.section>
@endif
