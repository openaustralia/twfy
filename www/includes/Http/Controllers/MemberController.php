<?php

namespace OpenAustralia\TWFY\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use OpenAustralia\TWFY\Enums\House;
use OpenAustralia\TWFY\Enums\MemberOrder;
use OpenAustralia\TWFY\Models\Member;
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
     * The house these pages are about.
     */
    abstract protected function house(): House;

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
     * The address of a member's profile, with the scheme and domain.
     */
    protected function absoluteProfileUrl(Member $member): string {
        return 'https://' . DOMAIN . $member->profile_url;
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
