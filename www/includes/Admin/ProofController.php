<?php

namespace OpenAustralia\TWFY\Admin;

use OpenAustralia\TWFY\ImportErrors;
use OpenAustralia\TWFY\ProofReport;

/**
 * The admin proof report: how much of each year's Hansard is final, proof or failed to import.
 */
class ProofController extends AdminController {

    /**
     * Where the parser's files are published, by folder then house folder, as the parser's pull-xml.rb reads them.
     */
    private const DATA_URL = 'https://data.openaustralia.org.au';

    /**
     * The segments of a bar, in the order they are drawn. The colours are checked for colour-blind separation across
     * every pair, and each also has an icon, so colour is never the only way to tell them apart.
     */
    public const SEGMENTS = [
        'final' => ['label' => 'Final', 'bar' => 'bg-[#0d9488]', 'pill' => 'bg-teal-100 text-teal-800'],
        'proof' => ['label' => 'Proof', 'bar' => 'bg-[#d97706]', 'pill' => 'bg-amber-100 text-amber-800'],
        'mixed' => ['label' => 'Mixed', 'bar' => 'bg-[#a21caf]', 'pill' => 'bg-fuchsia-100 text-fuchsia-800'],
        'unknown' => ['label' => 'Unknown', 'bar' => 'bg-[#7c6fd0]', 'pill' => 'bg-violet-100 text-violet-800'],
        'errors' => ['label' => 'Errors', 'bar' => 'bg-[#e11d48]', 'pill' => 'bg-rose-100 text-rose-800'],
    ];

    /**
     * The overview page: a bar for each year.
     */
    public function overview(): void {
        $this->page('admin_proof', function () {
            $years = ProofReport::years();
            $segments = self::segments($years);

            return $this->view('admin.proof', [
                'segments' => $segments,
                'years' => array_map(fn (array $row): array => [
                    'year' => $row['year'],
                    'url' => $row['total'] === 0 ? null : $this->adminUrl('proof_year.php', ['year' => $row['year']]),
                    'bar' => self::bar($row, $segments, (string) $row['year']),
                    'share' => self::finalShare($row),
                    'days' => self::days($row['total']),
                    'error_days' => self::errorDays($row['errors']),
                    'has_errors' => $row['errors'] > 0,
                ], $years),
            ]);
        });
    }

    /**
     * The page for one year: its months, and the days of one month when a month is chosen.
     */
    public function year(): void {
        $this->page('admin_proof', function () {
            $years = ProofReport::years();
            $held = array_column($years, 'year');
            $requested_year = (int) ($_GET['year'] ?? 0);
            $year = in_array($requested_year, $held, true) ? $requested_year : $held[0];
            $year_row = $years[array_search($year, $held, true)];
            $months = ProofReport::months($year);
            $requested_month = (int) ($_GET['month'] ?? 0);
            $month = in_array($requested_month, array_column($months, 'month'), true) ? $requested_month : null;
            $segments = self::segments($months);

            $trail = [['Admin', '/admin/'], ['Import status', $this->adminUrl('proof.php')], [(string) $year, $this->adminUrl('proof_year.php', ['year' => $year])]];
            if ($month !== null) {
                $trail[] = [self::monthName($month), null];
            }

            $index = array_search($year, $held, true);
            $columns = array_diff_key($segments, ['errors' => true]);

            return $this->view('admin.proof_year', [
                'trail' => $trail,
                'newer' => $index > 0 ? ['year' => $held[$index - 1], 'url' => $this->adminUrl('proof_year.php', ['year' => $held[$index - 1]])] : null,
                'older' => $index < count($held) - 1 ? ['year' => $held[$index + 1], 'url' => $this->adminUrl('proof_year.php', ['year' => $held[$index + 1]])] : null,
                'year' => $year,
                'year_bar' => self::bar($year_row, self::segments([$year_row]), (string) $year),
                'year_share' => self::finalShare($year_row),
                'segments' => $segments,
                'columns' => $columns,
                'months' => array_map(fn (array $row): array => [
                    'name' => self::monthName($row['month']),
                    'url' => $row['total'] === 0 ? null : $this->adminUrl('proof_year.php', ['year' => $year, 'month' => $row['month']]) . '#days',
                    'selected' => $row['month'] === $month,
                    'bar' => self::bar($row, $segments, self::monthName($row['month']) . ' ' . $year),
                    'counts' => array_map(fn (string $key): int => $row[$key], array_keys($columns)),
                    'total' => $row['total'],
                    'errors' => $row['errors'],
                    'last_changed' => $row['last_changed'] ?? '',
                ], $months),
                'month' => $month === null ? null : self::monthName($month) . ' ' . $year,
                'days' => $month === null ? [] : array_map(fn (array $day): array => $this->dayRow($day), ProofReport::days($year, $month)),
            ]);
        });
    }

    /**
     * The page listing the import errors recorded for one day and house.
     */
    public function errors(): void {
        $this->page('admin_proof', function () {
            $date = (string) ($_GET['date'] ?? '');
            $house = (string) ($_GET['house'] ?? '');
            $valid = preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $parts) && checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1]) && isset(ImportErrors::HOUSES[$house]);
            if (!$valid) {
                return $this->view('admin.proof_errors', ['trail' => [['Admin', '/admin/'], ['Import status', $this->adminUrl('proof.php')], ['Import errors', null]], 'valid' => false]);
            }

            $year = (int) $parts[1];
            $month = (int) $parts[2];
            $major = ImportErrors::HOUSES[$house];
            $month_url = $this->adminUrl('proof_year.php', ['year' => $year, 'month' => $month]) . '#days';

            return $this->view('admin.proof_errors', [
                'valid' => true,
                'trail' => [
                    ['Admin', '/admin/'],
                    ['Import status', $this->adminUrl('proof.php')],
                    [(string) $year, $this->adminUrl('proof_year.php', ['year' => $year])],
                    [self::monthName($month), $month_url],
                    [$date, null],
                ],
                'date' => $date,
                'house' => ProofReport::HOUSES[$major],
                'links' => self::sourceLinks($date, $house),
                'errors' => ProofReport::errorsForDay($date, $major),
                'month_url' => $month_url,
                'month' => self::monthName($month) . ' ' . $year,
            ]);
        });
    }

    /**
     * The segments to draw for some rows: Mixed and Errors only when one of them has such a day.
     *
     * @param list<array<string, mixed>> $rows
     *   Rows that each have a "mixed" and an "errors" count.
     *
     * @return array<string, array{label: string, bar: string, pill: string}>
     *   The segments, keyed by the row key that holds their count.
     */
    public static function segments(array $rows): array {
        $segments = self::SEGMENTS;
        foreach (['mixed', 'errors'] as $key) {
            if (!array_filter($rows, fn (array $row): bool => $row[$key] > 0)) {
                unset($segments[$key]);
            }
        }
        return $segments;
    }

    /**
     * A thin bar split by percent into the days in each state.
     *
     * @param array<string, int> $row
     *   The number of days in each state and the total.
     * @param array<string, array{label: string, bar: string, pill: string}> $segments
     *   The segments from segments().
     * @param string $name
     *   What the bar is for, such as a year, for screen readers.
     *
     * @return array{label: string, parts: list<array{class: string, width: float, title: string}>}
     *   The screen reader description and each part to draw. A bar with no sitting days has no parts.
     */
    public static function bar(array $row, array $segments, string $name): array {
        if ($row['total'] === 0) {
            return ['label' => $name . ': no sitting days', 'parts' => []];
        }
        $parts = [];
        foreach ($segments as $key => $segment) {
            if ($row[$key] === 0) {
                continue;
            }
            $percent = ProofReport::percent($row[$key], $row['total']);
            $parts[] = ['class' => $segment['bar'], 'width' => $percent, 'title' => $segment['label'] . ': ' . self::days($row[$key]) . ' (' . round($percent) . '%)'];
        }
        return ['label' => $name . ': ' . implode(', ', array_column($parts, 'title')), 'parts' => $parts];
    }

    /**
     * A count of days, with the right plural.
     */
    public static function days(int $count): string {
        return number_format($count) . ($count === 1 ? ' sitting day' : ' sitting days');
    }

    /**
     * A count of days with import errors, empty when there are none.
     */
    public static function errorDays(int $count): string {
        return $count === 0 ? '' : number_format($count) . ($count === 1 ? ' day with errors' : ' days with errors');
    }

    /**
     * The name of a month, from 1 to 12.
     */
    public static function monthName(int $month): string {
        return date('F', mktime(0, 0, 0, $month, 1));
    }

    /**
     * The share of final days, empty when there are no days.
     *
     * @param array<string, int> $row
     *   The number of days in each state and the total.
     */
    private static function finalShare(array $row): string {
        return $row['total'] === 0 ? '' : round(ProofReport::percent($row['final'], $row['total'])) . '% final';
    }

    /**
     * One row of the days table.
     *
     * @param array<string, mixed> $day
     *   A row from ProofReport::days().
     *
     * @return array<string, mixed>
     *   What the view shows.
     */
    private function dayRow(array $day): array {
        $status = ProofReport::status($day['proof'], $day['final'], $day['unknown'], $day['errors']);
        $key = strtolower($status);
        $house = (string) array_search($day['major'], ImportErrors::HOUSES, true);
        return [
            'date' => $day['date'],
            'url' => ($day['major'] === 1 ? '/debates/' : '/senate/') . '?d=' . $day['date'],
            'house' => ProofReport::HOUSES[$day['major']],
            'status' => $status,
            'status_key' => isset(self::SEGMENTS[$key]) ? $key : null,
            'status_colours' => self::SEGMENTS[$key]['pill'] ?? 'bg-slate-100 text-slate-700',
            'proof' => $day['proof'],
            'final' => $day['final'],
            'unknown' => $day['unknown'],
            'errors' => $day['errors'],
            'errors_label' => $day['errors'] === 1 ? '1 error' : $day['errors'] . ' errors',
            'errors_url' => $day['errors'] === 0 ? null : $this->adminUrl('proof_errors.php', ['date' => $day['date'], 'house' => $house]),
            'last_changed' => $day['last_changed'] ?? '',
        ];
    }

    /**
     * Links to where a day's source files are: the page on Parliament House and the two files we publish.
     *
     * The origxml file is missing for some days, so its link can lead to a not found page.
     *
     * @param string $date
     *   A valid day, as Y-m-d.
     * @param string $house
     *   Either representatives or senate.
     *
     * @return array<string, string>
     *   Each address by what it links to.
     */
    private static function sourceLinks(string $date, string $house): array {
        [$year, $month, $day] = array_map('intval', explode('-', $date));
        $letter = $house === 'senate' ? 's' : 'r';
        $aph = 'https://parlinfo.aph.gov.au/parlInfo/search/display/display.w3p;adv=yes;orderBy=_fragment_number,doc_date-rev;page=0;query=Dataset%3Ahansard' . $letter
            . ',hansard' . $letter . '80%20Date%3A' . $day . '%2F' . $month . '%2F' . $year . ';rec=0;resCount=Default';
        $file = self::DATA_URL . '/%s/' . $house . '_debates/' . $date . '.xml';
        return [
            'The day on Parliament House (APH)' => $aph,
            'The original XML from APH (origxml)' => sprintf($file, 'origxml'),
            'The parsed XML we load (scrapedxml)' => sprintf($file, 'scrapedxml'),
        ];
    }

}
