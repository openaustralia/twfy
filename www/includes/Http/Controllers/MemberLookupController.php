<?php

namespace OpenAustralia\TWFY\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use OpenAustralia\TWFY\Enums\House;
use OpenAustralia\TWFY\Http\Visitor;
use OpenAustralia\TWFY\Models\Member;
use OpenAustralia\TWFY\Models\PostcodeLookup;
use OpenAustralia\TWFY\Parliament\MemberUrls;
use OpenAustralia\TWFY\Parliament\Person;
use Symfony\Component\HttpFoundation\Response;

/**
 * /mp/ and /senator/ without a name in the path.
 *
 * It finds someone from the query string, or else the visitor's electorate, and redirects to their profile. With
 * nothing to go on, it asks for a postcode. The parameters are ?pid (or ?p) for a person, ?m for a term, ?pc for a
 * postcode, ?c for an electorate, ?n for a name, and ?recent=1 for someone's latest speeches.
 */
class MemberLookupController {

    /**
     * Takes what it needs to know about the visitor.
     */
    public function __construct(private readonly Visitor $visitor) {
    }

    /**
     * Answers a lookup.
     */
    public function __invoke(Request $request): View|Response {
        $personId = $this->id($request, 'pid') ?? $this->id($request, 'p');

        if ($this->text($request, 'recent') !== '') {
            $speaker = $personId ?? $this->visitor->member()?->person_id;
            if ($speaker) {
                // away() keeps the slash before the query, which Apache would otherwise redirect to add.
                return redirect()->away(MemberUrls::speeches($speaker));
            }
        }

        $memberId = $this->id($request, 'm');
        if ($memberId !== null) {
            return $this->toPerson($request, Member::query()->find($memberId)?->person_id);
        }
        if ($personId !== null) {
            return $this->toPerson($request, $personId);
        }

        return $this->byWords($request);
    }

    /**
     * A lookup by postcode, name or electorate, or else by who is visiting.
     */
    private function byWords(Request $request): View|Response {
        $postcode = $this->text($request, 'pc');
        $electorate = MemberUrls::words($this->text($request, 'c'));
        $name = MemberUrls::words($this->text($request, 'n'));

        if ($postcode !== '') {
            return $this->byPostcode($request, $postcode, $electorate);
        }
        if ($name !== '') {
            return redirect(MemberUrls::profile($this->house($request), $name, $electorate));
        }

        $member = $electorate === '' ? $this->visitor->member() : Member::latestInElectorate($electorate);

        return $this->toPerson($request, $member?->person_id);
    }

    /**
     * Redirects to the Representative for a postcode and remembers the electorate, or asks which of several.
     */
    private function byPostcode(Request $request, string $postcode, string $electorate): View|Response {
        $postcode = preg_replace('/[^a-z0-9 ]/i', '', $postcode);
        if (!validate_postcode($postcode)) {
            return $this->form($request, "Sorry, $postcode isn't a valid postcode");
        }

        // An electorate chosen from the list of several comes back with the postcode.
        $electorates = $electorate === ''
          ? PostcodeLookup::electoratesFor(str_replace(' ', '', $postcode))
          : collect([$electorate]);
        if ($electorates->isEmpty()) {
            return $this->form($request, "Sorry, $postcode isn't a known postcode");
        }
        if ($electorates->count() > 1) {
            return view('members.electorates', [
                'page' => $this->page($request),
                'postcode' => $postcode,
                'electorates' => $electorates->mapWithKeys(fn (string $name): array => [$name => Member::latestInElectorate($name)]),
            ]);
        }

        $member = Member::latestInElectorate($electorates->first());
        $answer = $this->toPerson($request, $member?->person_id);

        // Only an electorate that leads to a profile is worth keeping.
        return $answer instanceof RedirectResponse
          ? $this->visitor->rememberElectorate($member->constituency, $answer)
          : $answer;
    }

    /**
     * A redirect to a person's profile, or the postcode form if there is no such person.
     */
    private function toPerson(Request $request, ?int $personId): View|RedirectResponse {
        $person = $personId ? Person::find($personId) : null;

        return $person ? redirect($person->latest()->profile_url) : $this->form($request);
    }

    /**
     * The postcode form, with any reason the last try failed.
     */
    private function form(Request $request, ?string $error = null): View {
        return view('members.lookup', [
            'page' => $this->page($request),
            'postcode' => $this->text($request, 'pc'),
            'error' => $error,
        ]);
    }

    /**
     * The house the address is about.
     */
    private function house(Request $request): House {
        return $request->routeIs('senators.lookup') ? House::Senate : House::Representatives;
    }

    /**
     * The legacy page key, which sets the title and the menu.
     */
    private function page(Request $request): string {
        return $this->house($request) === House::Senate ? 'peer' : 'mp';
    }

    /**
     * A query parameter as text. Anything but a single value is empty.
     */
    private function text(Request $request, string $name): string {
        $value = $request->query($name);
        return is_string($value) ? $value : '';
    }

    /**
     * A query parameter that is an id.
     */
    private function id(Request $request, string $name): ?int {
        $value = $this->text($request, $name);
        return ctype_digit($value) ? (int) $value : null;
    }

}
