<?php

namespace OpenAustralia\TWFY\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use OpenAustralia\TWFY\Enums\House;
use OpenAustralia\TWFY\Http\Visitor;
use OpenAustralia\TWFY\Models\Member;
use OpenAustralia\TWFY\Parliament\MemberUrls;
use OpenAustralia\TWFY\Parliament\Person;
use OpenAustralia\TWFY\Parliament\RecentSpeeches;

/**
 * Pages about members of the House of Representatives.
 */
class RepresentativeController extends MemberController {

    /**
     * Takes what it needs to know about the visitor.
     */
    public function __construct(private readonly Visitor $visitor) {
    }

    /**
     * One Representative's profile.
     *
     * A name that several people share asks which one. A name alone, with the wrong electorate, or spelt with "+",
     * redirects to the full address.
     */
    public function show(RecentSpeeches $speeches, string $name, ?string $constituency = null): View|RedirectResponse {
        $oldSpelling = $this->usesPlus($name, $constituency);
        // Old links may say "/mp/jane citizen mp".
        if ($constituency === null) {
            $name = preg_replace('/ mp$/i', '', MemberUrls::words($name));
        }

        $matches = $this->matches($name, $constituency);
        $wrongElectorate = $constituency !== null && $matches->isEmpty();
        if ($wrongElectorate) {
            $matches = $this->matches($name, null);
        }
        abort_if($matches->isEmpty(), 404);

        if ($matches->count() > 1) {
            return $this->choose($matches);
        }

        $person = Person::find($matches->first()->person_id);
        if ($constituency === null || $wrongElectorate || $oldSpelling) {
            return redirect($person->latest()->profile_url);
        }

        return $this->profile($person, $speeches);
    }

    /**
     * {@inheritdoc}
     */
    protected function house(): House {
        return House::Representatives;
    }

    /**
     * {@inheritdoc}
     */
    protected function profilePage(Person $person): string {
        // The visitor's own Representative gets the "Your Representative" menu entry.
        $theirs = $person->sitsIn($this->house())
          && strcasecmp($this->visitor->electorate(), $person->latest()->constituency) === 0;

        return $theirs ? 'yourmp' : 'mp';
    }

    /**
     * {@inheritdoc}
     */
    protected function indexView(): string {
        return 'representatives.index';
    }

    /**
     * {@inheritdoc}
     */
    protected function indexData(): array {
        return ['yours' => $this->visitor->member($this->house())];
    }

    /**
     * {@inheritdoc}
     */
    protected function csvName(): string {
        return 'representatives.csv';
    }

    /**
     * {@inheritdoc}
     */
    protected function csvHeadings(): array {
        return ['Person ID', 'First name', 'Last name', 'Party', 'Division', 'URI'];
    }

    /**
     * {@inheritdoc}
     */
    protected function csvRow(Member $member): array {
        return [
            $member->person_id,
            $member->display_first_name,
            $member->display_last_name,
            $member->party_label,
            $member->display_constituency,
            $this->absoluteProfileUrl($member),
        ];
    }

    /**
     * A list of the people who share a name.
     *
     * @param \Illuminate\Support\Collection<int, Member> $matches
     *   One term for each person.
     */
    private function choose(Collection $matches): View {
        return view('members.choose', ['page' => 'mp', 'matches' => $matches]);
    }

}
