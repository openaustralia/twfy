<?php

namespace OpenAustralia\TWFY\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection as SupportCollection;
use OpenAustralia\TWFY\Enums\House;
use OpenAustralia\TWFY\Enums\MemberOrder;
use OpenAustralia\TWFY\Models\Member;
use OpenAustralia\TWFY\Parliament\MemberFacts;
use OpenAustralia\TWFY\Parliament\MemberLinks;
use OpenAustralia\TWFY\Parliament\MemberProfile;
use OpenAustralia\TWFY\Parliament\MemberStatistics;
use OpenAustralia\TWFY\Parliament\MemberUrls;
use OpenAustralia\TWFY\Parliament\Person;
use OpenAustralia\TWFY\Parliament\RecentSpeeches;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * What the pages about Representatives and Senators share.
 */
abstract class MemberController {

    /**
     * The sitting members of one house, as a page or, with ?f=csv, as a spreadsheet.
     */
    public function index(Request $request): View|StreamedResponse {
        $order = MemberOrder::fromQuery($request->query('o'));
        $members = Member::sittingList($this->house(), $order);

        if ($request->query('f') === 'csv') {
            return $this->csv($members, $order);
        }

        return view($this->indexView(), ['members' => $members, 'order' => $order] + $this->indexData());
    }

    /**
     * One person's profile. An address without a constituency, or spelt with "+", redirects to the full one.
     */
    public function show(RecentSpeeches $speeches, string $name, ?string $constituency = null): View|RedirectResponse {
        $matches = $this->matches($name, $constituency);
        abort_if($matches->isEmpty(), 404);

        $person = Person::find($matches->first()->person_id);

        return $constituency === null || $this->usesPlus($name, $constituency)
          ? redirect($person->latest()->profile_url)
          : $this->profile($person, $speeches);
    }

    /**
     * The house these pages are about.
     */
    abstract protected function house(): House;

    /**
     * The legacy page key for a person's profile, which sets the title and the menu.
     */
    abstract protected function profilePage(Person $person): string;

    /**
     * The Blade view for the list.
     */
    abstract protected function indexView(): string;

    /**
     * The spreadsheet's file name.
     */
    abstract protected function csvName(): string;

    /**
     * The spreadsheet's column headings, before any for the sort order.
     *
     * @return list<string>
     *   Headings.
     */
    abstract protected function csvHeadings(): array;

    /**
     * One member's spreadsheet cells, matching csvHeadings().
     *
     * @return list<int|string>
     *   Cells.
     */
    abstract protected function csvRow(Member $member): array;

    /**
     * Anything more the list view shows.
     *
     * @return array<string, mixed>
     *   View data.
     */
    protected function indexData(): array {
        return [];
    }

    /**
     * Whether an address is an old one that spells spaces as "+".
     */
    protected function usesPlus(string $name, ?string $constituency): bool {
        return str_contains($name . $constituency, '+');
    }

    /**
     * The people an address names, each as their latest matching term.
     *
     * @param string $name
     *   The name from the address.
     * @param string|null $constituency
     *   The electorate or state from the address, if it has one.
     *
     * @return \Illuminate\Support\Collection<int, Member>
     *   One term for each person.
     */
    protected function matches(string $name, ?string $constituency): SupportCollection {
        return Person::termsNamed(
            $this->house(),
            MemberUrls::words($name),
            $constituency === null ? null : MemberUrls::words($constituency),
        );
    }

    /**
     * The page about one person.
     */
    protected function profile(Person $person, RecentSpeeches $speeches): View {
        $profile = new MemberProfile($person);
        $facts = MemberFacts::for($person);
        $feed = $this->feedPath($person);
        [$photo, $photoSize] = find_rep_image($person->id);

        return view('members.show', [
            'page' => $this->profilePage($person),
            // The legacy layout prints the title as it is.
            'metadata' => array_filter(['subtitle' => e($profile->pageTitle()), 'rss' => $feed]),
            'profile' => $profile,
            'person' => $person,
            'memberTitle' => House::from($person->latest()->house)->memberTitle(),
            'facts' => $facts,
            'links' => MemberLinks::for($profile, $facts),
            'statistics' => new MemberStatistics($person, $facts),
            'speeches' => $speeches->for($person->id),
            'photo' => $photo,
            'photoSize' => $photoSize,
            'feed' => $feed,
        ]);
    }

    /**
     * The address of a member's profile, with the scheme and domain.
     */
    protected function absoluteProfileUrl(Member $member): string {
        return 'https://' . DOMAIN . $member->profile_url;
    }

    /**
     * The RSS feed of a person's speeches, if scripts/mprss.php has written one.
     */
    private function feedPath(Person $person): ?string {
        $path = 'rss/mp/' . $person->id . '.rdf';
        return file_exists(BASEDIR . '/' . $path) ? $path : null;
    }

    /**
     * The list as a CSV download.
     *
     * @param \Illuminate\Database\Eloquent\Collection<int, Member> $members
     *   The members, in order.
     * @param \OpenAustralia\TWFY\Enums\MemberOrder $order
     *   The order asked for, which may add a column.
     */
    private function csv(Collection $members, MemberOrder $order): StreamedResponse {
        $debates = $order === MemberOrder::Debates;

        return response()->streamDownload(function () use ($members, $debates): void {
            $output = fopen('php://output', 'w');
            $headings = $this->csvHeadings();
            if ($debates) {
                $headings[] = 'Debates spoken in the last year';
            }
            fputcsv($output, $headings, escape: '');
            foreach ($members as $member) {
                $row = $this->csvRow($member);
                if ($debates) {
                    $row[] = (int) $member->personInfo->first()->data_value;
                }
                fputcsv($output, $row, escape: '');
            }
            fclose($output);
        }, $this->csvName(), ['Content-Type' => 'text/csv;charset=UTF-8']);
    }

}
