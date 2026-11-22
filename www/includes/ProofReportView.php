<?php

namespace OpenAustralia\TWFY;

/**
 * The HTML of the admin proof report pages: the coloured segments, the bars, the tables and the links.
 *
 * Every method returns HTML and escapes what it prints. The pages in www/docs/admin only call overview() and year(),
 * after the admin check in PAGE::page_start() has passed.
 */
class ProofReportView {

    /**
     * Where the parser's files are published, by folder then house folder, as the parser's pull-xml.rb reads them.
     */
    private const DATA_URL = 'https://data.openaustralia.org.au';

    public const LINK_CLASSES = '!text-teal-700 !no-underline hover:!underline';

    /**
     * The segments of a bar, in the order they are drawn. The colours are checked for colour-blind separation across
     * every pair, and each also has an icon, so colour is never the only way to tell them apart.
     */
    private const SEGMENTS = [
        'final' => ['label' => 'Final', 'bar' => 'bg-[#0d9488]', 'pill' => 'bg-teal-100 text-teal-800'],
        'proof' => ['label' => 'Proof', 'bar' => 'bg-[#d97706]', 'pill' => 'bg-amber-100 text-amber-800'],
        'mixed' => ['label' => 'Mixed', 'bar' => 'bg-[#a21caf]', 'pill' => 'bg-fuchsia-100 text-fuchsia-800'],
        'unknown' => ['label' => 'Unknown', 'bar' => 'bg-[#7c6fd0]', 'pill' => 'bg-violet-100 text-violet-800'],
        'errors' => ['label' => 'Errors', 'bar' => 'bg-[#e11d48]', 'pill' => 'bg-rose-100 text-rose-800'],
    ];

    /**
     * The inside of a 16 by 16 outline icon for each segment, drawn in the colour of the text around it.
     */
    private const ICONS = [
        'final' => '<circle cx="8" cy="8" r="6.5"/><path d="M5 8.2l2 2 4-4.4" stroke-linecap="round" stroke-linejoin="round"/>',
        'proof' => '<path d="M3 13l.6-2.8L10.8 3l2.2 2.2-7.2 7.2L3 13z" stroke-linejoin="round"/><path d="M9.6 4.2l2.2 2.2"/>',
        'mixed' => '<circle cx="8" cy="8" r="6.5"/><path d="M8 1.5a6.5 6.5 0 0 1 0 13z" fill="currentColor"/>',
        'unknown' => '<circle cx="8" cy="8" r="6.5"/><path d="M6.2 6.3a1.9 1.9 0 1 1 2.7 1.7c-.6.3-.9.7-.9 1.3M8 11.3v.01" stroke-linecap="round"/>',
        'errors' => '<circle cx="8" cy="8" r="6.5"/><path d="M8 4.5v4M8 11v.01" stroke-linecap="round"/>',
    ];

    public const TABLE = '<table class="w-full max-w-4xl !m-0 text-sm border-collapse">';

    public const HEADING_ROW = '<thead><tr class="bg-slate-100 text-left text-xs uppercase tracking-wide text-slate-600">';

    /**
     * The overview page: a bar for each year.
     */
    public static function overview(): string {
        $years = ProofReport::years();
        $segments = self::segments($years);

        $html = self::breadcrumbs([['Admin', '/admin/'], ['Import status', null]]);
        $html .= '<p class="text-sm text-slate-600 max-w-3xl">Each bar shows the share of the sitting days we hold for a year. A sitting day is one house\'s sitting, so a date when both houses sat counts twice. '
            . 'Choose a year to see its months and days.</p>';
        $html .= self::legend($segments);
        $html .= '<ol class="max-w-4xl !m-0 p-0 list-none">';
        foreach ($years as $row) {
            $html .= '<li class="flex items-center gap-4 !ml-0 px-3 py-2 rounded hover:bg-slate-50">'
                . self::yearLabel($row)
                . self::bar($row, $segments, (string) $row['year'])
                . self::finalShare($row)
                . '</li>';
        }
        return $html . '</ol>';
    }

    /**
     * The page for one year: its months, and the days of one month when a month is chosen.
     *
     * @param int $requested_year
     *   The year asked for. A year we don't list gives the newest.
     * @param int $requested_month
     *   The month asked for, from 1 to 12. Anything else shows no days.
     */
    public static function year(int $requested_year, int $requested_month): string {
        $years = ProofReport::years();
        $held = array_column($years, 'year');
        $year = in_array($requested_year, $held, true) ? $requested_year : $held[0];
        $year_row = $years[array_search($year, $held, true)];
        $months = ProofReport::months($year);
        $month = in_array($requested_month, array_column($months, 'month'), true) ? $requested_month : null;

        $trail = [['Admin', '/admin/'], ['Import status', self::link('proof.php')], [(string) $year, self::link('proof_year.php', ['year' => $year])]];
        if ($month !== null) {
            $trail[] = [self::monthName($month), null];
        }

        $html = self::breadcrumbs($trail) . self::yearNavigation($held, $year);
        $html .= '<div class="flex items-center gap-4 max-w-4xl my-4">'
            . self::bar($year_row, self::segments([$year_row]), (string) $year)
            . self::finalShare($year_row)
            . '</div>';
        $html .= self::legend(self::segments($months));
        $html .= '<p class="text-sm text-slate-600 max-w-3xl">Each row is a month. A sitting day is one house\'s sitting, so a date when both houses sat counts twice. Choose a month to see its days. '
            . '"Last changed" is when a row last changed in our database, not when we last read it from Parliament House.</p>';
        $html .= self::monthsTable($months, $year, $month);

        if ($month !== null) {
            $html .= self::daysSection(ProofReport::days($year, $month), $year, $month);
        }
        return $html;
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
     * The icon for a segment at a size, as an inline SVG that screen readers skip.
     *
     * @param string $key
     *   A key of SEGMENTS.
     * @param int $size
     *   Width and height in pixels.
     */
    public static function icon(string $key, int $size): string {
        return '<svg class="inline-block shrink-0" width="' . $size . '" height="' . $size . '" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">'
            . self::ICONS[$key] . '</svg>';
    }

    /**
     * The key of colours, one entry for each segment.
     *
     * @param array<string, array{label: string, bar: string, pill: string}> $segments
     *   The segments from segments().
     */
    public static function legend(array $segments): string {
        $html = '<ul class="flex flex-wrap gap-x-5 gap-y-1 my-4 p-0 list-none text-sm text-slate-700">';
        foreach ($segments as $key => $segment) {
            $html .= '<li class="flex items-center gap-2"><span class="inline-flex items-center justify-center w-5 h-5 rounded text-white ' . $segment['bar'] . '">'
                . self::icon($key, 14) . '</span>' . $segment['label'] . '</li>';
        }
        return $html . '</ul>';
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
     */
    public static function bar(array $row, array $segments, string $name): string {
        if ($row['total'] === 0) {
            return '<div class="flex flex-1 h-3 rounded bg-slate-100" role="img" aria-label="' . htmlspecialchars($name) . ': no sitting days"></div>';
        }
        $parts = [];
        $html = '';
        foreach ($segments as $key => $segment) {
            if ($row[$key] === 0) {
                continue;
            }
            $percent = ProofReport::percent($row[$key], $row['total']);
            $description = $segment['label'] . ': ' . self::days($row[$key]) . ' (' . round($percent) . '%)';
            $parts[] = $description;
            $html .= '<div class="' . $segment['bar'] . '" style="width:' . $percent . '%;min-width:3px" title="' . htmlspecialchars($description) . '"></div>';
        }
        return '<div class="flex flex-1 gap-0.5 h-3 overflow-hidden rounded bg-white" role="img" aria-label="'
            . htmlspecialchars($name . ': ' . implode(', ', $parts)) . '">' . $html . '</div>';
    }

    /**
     * The address of one of the report's pages, with only the parameters that have a value. Not escaped for HTML.
     *
     * @param string $page
     *   The file name in the admin folder, such as proof_year.php.
     * @param array<string, int|string|null> $params
     *   The query parameters.
     */
    public static function link(string $page, array $params = []): string {
        $query = http_build_query(array_filter($params, fn ($value): bool => $value !== null && $value !== ''));
        return '/admin/' . $page . ($query === '' ? '' : '?' . $query);
    }

    /**
     * The breadcrumb trail, with the last item as the page you are on.
     *
     * @param list<array{string, string|null}> $trail
     *   A label and an address for each step. The address of the last step is ignored.
     */
    public static function breadcrumbs(array $trail): string {
        $html = '<nav aria-label="Breadcrumb" class="mb-4"><ol class="flex flex-wrap items-center gap-2 !m-0 p-0 list-none text-sm text-slate-500">';
        $last = count($trail) - 1;
        foreach ($trail as $index => [$label, $url]) {
            $label = htmlspecialchars($label);
            if ($index === $last || $url === null) {
                $html .= '<li class="!ml-0 font-semibold text-slate-900" aria-current="page">' . $label . '</li>';
                continue;
            }
            $html .= '<li class="!ml-0 flex items-center gap-2"><a class="' . self::LINK_CLASSES . '" href="' . htmlspecialchars($url) . '">' . $label . '</a>'
                . '<span aria-hidden="true">&rsaquo;</span></li>';
        }
        return $html . '</ol></nav>';
    }

    /**
     * A count of days, with the right plural.
     */
    public static function days(int $count): string {
        return number_format($count) . ($count === 1 ? ' sitting day' : ' sitting days');
    }

    /**
     * The name of a month, from 1 to 12.
     */
    public static function monthName(int $month): string {
        return date('F', mktime(0, 0, 0, $month, 1));
    }

    /**
     * The page listing the import errors recorded for one day and house.
     *
     * @param string $date
     *   The day, as Y-m-d.
     * @param string $house
     *   Either representatives or senate.
     */
    public static function errors(string $date, string $house): string {
        $valid = preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $parts) && checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1]) && isset(ImportErrors::HOUSES[$house]);
        if (!$valid) {
            return self::breadcrumbs([['Admin', '/admin/'], ['Import status', self::link('proof.php')], ['Import errors', null]])
                . '<p class="text-sm text-slate-600">That is not a day and house we can show errors for.</p>';
        }

        $year = (int) $parts[1];
        $month = (int) $parts[2];
        $house_name = ProofReport::HOUSES[ImportErrors::HOUSES[$house]];
        $errors = ProofReport::errorsForDay($date, ImportErrors::HOUSES[$house]);
        $month_link = self::link('proof_year.php', ['year' => $year, 'month' => $month]) . '#days';

        $html = self::breadcrumbs([
            ['Admin', '/admin/'],
            ['Import status', self::link('proof.php')],
            [(string) $year, self::link('proof_year.php', ['year' => $year])],
            [self::monthName($month), $month_link],
            [$date, null],
        ]);
        $html .= '<h3 class="mt-0 mb-2 mx-0 text-base font-semibold text-slate-900">Import errors for ' . htmlspecialchars($date) . ', ' . $house_name . '</h3>'
            . '<p class="text-sm text-slate-600 max-w-3xl">The parser records errors while it reads a day from Parliament House, and the loader records them while it puts the day into the database. Each clears its own errors for a day when it next handles the day without one.</p>';

        $html .= self::sourceLinks($date, $house);

        if (!$errors) {
            return $html . '<p class="text-sm text-slate-600">No errors are recorded for this day.</p>';
        }

        $html .= '<div class="overflow-x-auto">' . self::TABLE . self::HEADING_ROW
            . '<th class="px-3 py-2 font-semibold">Found by</th><th class="px-3 py-2 font-semibold">Error</th><th class="px-3 py-2 font-semibold">Recorded</th></tr></thead><tbody>';
        foreach ($errors as $error) {
            $html .= '<tr class="border-0 border-b border-solid border-slate-200 even:bg-slate-50 align-top">'
                . '<td class="px-3 py-2 whitespace-nowrap">' . ($error['source'] === 'parser' ? 'Parser' : 'Loader') . '</td>'
                . '<td class="px-3 py-2"><pre class="m-0 whitespace-pre-wrap break-words font-mono text-xs text-slate-900">' . htmlspecialchars($error['message']) . '</pre></td>'
                . '<td class="px-3 py-2 text-slate-600 whitespace-nowrap tabular-nums">' . htmlspecialchars($error['created_at'] ?? '') . '</td></tr>';
        }
        return $html . '</tbody></table></div>'
            . '<p class="mt-4 text-sm font-semibold"><a class="' . self::LINK_CLASSES . '" href="' . htmlspecialchars($month_link) . '">&larr; Back to ' . self::monthName($month) . ' ' . $year . '</a></p>';
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
     */
    private static function sourceLinks(string $date, string $house): string {
        [$year, $month, $day] = array_map('intval', explode('-', $date));
        $letter = $house === 'senate' ? 's' : 'r';
        $aph = 'https://parlinfo.aph.gov.au/parlInfo/search/display/display.w3p;adv=yes;orderBy=_fragment_number,doc_date-rev;page=0;query=Dataset%3Ahansard' . $letter
            . ',hansard' . $letter . '80%20Date%3A' . $day . '%2F' . $month . '%2F' . $year . ';rec=0;resCount=Default';
        $file = self::DATA_URL . '/%s/' . $house . '_debates/' . $date . '.xml';
        $links = [
            'The day on Parliament House (APH)' => $aph,
            'The original XML from APH (origxml)' => sprintf($file, 'origxml'),
            'The parsed XML we load (scrapedxml)' => sprintf($file, 'scrapedxml'),
        ];

        $html = '<ul class="my-4 pl-5 text-sm">';
        foreach ($links as $label => $url) {
            $html .= '<li><a class="' . self::LINK_CLASSES . '" href="' . htmlspecialchars($url) . '">' . $label . '</a></li>';
        }
        return $html . '</ul>';
    }

    /**
     * A link to a day's errors with how many there are, or nothing for a day without any.
     *
     * @param array<string, mixed> $day
     *   A row from ProofReport::days().
     */
    private static function errorsLink(array $day): string {
        if ($day['errors'] === 0) {
            return '';
        }
        $house = (string) array_search($day['major'], ImportErrors::HOUSES, true);
        $label = $day['errors'] === 1 ? '1 error' : $day['errors'] . ' errors';
        return '<a class="inline-flex items-center gap-1 whitespace-nowrap font-semibold !text-rose-700 !no-underline hover:!underline" href="'
            . htmlspecialchars(self::link('proof_errors.php', ['date' => $day['date'], 'house' => $house])) . '">' . self::icon('errors', 16) . $label . '</a>';
    }

    /**
     * A link to a year, or just its number when we hold no days in it.
     *
     * @param array<string, int> $row
     *   A row from ProofReport::years().
     */
    private static function yearLabel(array $row): string {
        if ($row['total'] === 0) {
            return '<span class="w-14 text-sm text-slate-400">' . $row['year'] . '</span>';
        }
        return '<a class="w-14 text-sm font-semibold ' . self::LINK_CLASSES . '" href="'
            . htmlspecialchars(self::link('proof_year.php', ['year' => $row['year']])) . '">' . $row['year'] . '</a>';
    }

    /**
     * The share of final days and the number of days, to the right of a bar.
     *
     * @param array<string, int> $row
     *   The number of days in each state and the total.
     */
    private static function finalShare(array $row): string {
        $share = $row['total'] === 0 ? '' : round(ProofReport::percent($row['final'], $row['total'])) . '% final';
        return '<span class="w-24 text-right text-sm tabular-nums text-slate-700">' . $share . '</span>'
            . '<span class="w-24 text-right text-xs tabular-nums text-slate-500">' . self::days($row['total']) . '</span>'
            . '<span class="w-28 text-right text-xs tabular-nums ' . ($row['errors'] > 0 ? 'font-semibold text-rose-700' : 'text-slate-400') . '">' . self::errorDays($row['errors']) . '</span>';
    }

    /**
     * A count of days with import errors, empty when there are none.
     */
    public static function errorDays(int $count): string {
        return $count === 0 ? '' : number_format($count) . ($count === 1 ? ' day with errors' : ' days with errors');
    }

    /**
     * Links to the newer and the older year beside this one.
     *
     * @param list<int> $held
     *   The listed years, newest first.
     * @param int $year
     *   The year the page is for.
     */
    private static function yearNavigation(array $held, int $year): string {
        $index = array_search($year, $held, true);
        $html = '<div class="flex flex-wrap items-center justify-between max-w-4xl gap-6 text-sm font-semibold">';
        $html .= $index > 0
            ? '<a class="' . self::LINK_CLASSES . '" href="' . htmlspecialchars(self::link('proof_year.php', ['year' => $held[$index - 1]])) . '">&larr; ' . $held[$index - 1] . '</a>'
            : '<span></span>';
        if ($index < count($held) - 1) {
            $html .= '<a class="' . self::LINK_CLASSES . '" href="' . htmlspecialchars(self::link('proof_year.php', ['year' => $held[$index + 1]])) . '">' . $held[$index + 1] . ' &rarr;</a>';
        }
        return $html . '</div>';
    }

    /**
     * One row for each month, with a link to the days of every month that has some.
     *
     * @param list<array<string, mixed>> $months
     *   Rows from ProofReport::months().
     * @param int $year
     *   The year the months belong to, for the links.
     * @param int|null $selected
     *   The month whose days are shown below, or null.
     */
    private static function monthsTable(array $months, int $year, ?int $selected): string {
        $segments = self::segments($months);
        $html = '<div class="overflow-x-auto">' . self::TABLE . self::HEADING_ROW
            . '<th class="px-3 py-2 font-semibold">Month</th><th class="px-3 py-2 font-semibold">Share of sitting days</th>';
        // Errors has its own column below, which is there even for a year with none.
        foreach (array_diff_key($segments, ['errors' => true]) as $segment) {
            $html .= '<th class="px-3 py-2 font-semibold text-right">' . $segment['label'] . '</th>';
        }
        $html .= '<th class="px-3 py-2 font-semibold text-right">Sitting days</th><th class="px-3 py-2 font-semibold text-right">Days with errors</th><th class="px-3 py-2 font-semibold">Last changed</th></tr></thead><tbody>';

        foreach ($months as $row) {
            $is_selected = $row['month'] === $selected;
            $name = self::monthName($row['month']);
            $html .= '<tr class="border-0 border-b border-solid border-slate-200 hover:bg-teal-50' . ($is_selected ? ' bg-slate-100' : '') . '">'
                . '<td class="px-3 py-2 whitespace-nowrap border-0 border-l-4 border-solid ' . ($is_selected ? 'border-teal-700' : 'border-transparent') . '">';
            if ($row['total'] === 0) {
                $html .= '<span class="text-slate-400">' . $name . '</span>';
            } else {
                $html .= '<a class="font-semibold ' . self::LINK_CLASSES . '"' . ($is_selected ? ' aria-current="true"' : '') . ' href="'
                    . htmlspecialchars(self::link('proof_year.php', ['year' => $year, 'month' => $row['month']])) . '#days">' . $name . '</a>';
            }
            $html .= '</td><td class="px-3 py-2 w-48"><div class="flex">' . self::bar($row, $segments, $name . ' ' . $year) . '</div></td>';
            foreach (array_keys(array_diff_key($segments, ['errors' => true])) as $key) {
                $html .= '<td class="px-3 py-2 text-right tabular-nums">' . $row[$key] . '</td>';
            }
            $html .= '<td class="px-3 py-2 text-right tabular-nums">' . $row['total'] . '</td>'
                . '<td class="px-3 py-2 text-right tabular-nums ' . ($row['errors'] > 0 ? 'font-semibold text-rose-700' : 'text-slate-400') . '">' . $row['errors'] . '</td>'
                . '<td class="px-3 py-2 text-slate-600 whitespace-nowrap tabular-nums">' . htmlspecialchars($row['last_changed'] ?? '') . '</td></tr>';
        }
        return $html . '</tbody></table></div>';
    }

    /**
     * The days of one month: the heading and the table. A month has at most 62, so there is no paging.
     *
     * @param list<array<string, mixed>> $days
     *   Rows from ProofReport::days().
     * @param int $year
     *   The year of the month.
     * @param int $month
     *   The month, from 1 to 12.
     */
    private static function daysSection(array $days, int $year, int $month): string {
        $html = '<h3 id="days" class="mt-8 mb-4 mx-0 text-base font-semibold text-slate-900">' . self::monthName($month) . ' ' . $year . '</h3>';

        if (!$days) {
            return $html . '<p class="text-sm text-slate-600">We hold no sitting days in this month.</p>';
        }

        $html .= '<div class="overflow-x-auto">' . self::TABLE . self::HEADING_ROW;
        foreach (['Date', 'House', 'Status'] as $heading) {
            $html .= '<th class="px-3 py-2 font-semibold">' . $heading . '</th>';
        }
        foreach (['Proof rows', 'Final rows', 'Unknown rows', 'Errors'] as $heading) {
            $html .= '<th class="px-3 py-2 font-semibold text-right">' . $heading . '</th>';
        }
        $html .= '<th class="px-3 py-2 font-semibold">Last changed</th></tr></thead><tbody>';
        foreach ($days as $day) {
            $status = ProofReport::status($day['proof'], $day['final'], $day['unknown'], $day['errors']);
            $path = $day['major'] === 1 ? '/debates/' : '/senate/';
            $date = htmlspecialchars($day['date']);
            $key = strtolower($status);
            $pill = self::SEGMENTS[$key]['pill'] ?? 'bg-slate-100 text-slate-700';
            $pill_icon = isset(self::ICONS[$key]) ? self::icon($key, 12) : '';
            $row_colours = $day['errors'] > 0 ? 'bg-rose-50 hover:bg-rose-100' : 'even:bg-slate-50 hover:bg-teal-50';
            $html .= '<tr class="border-0 border-b border-solid border-slate-200 ' . $row_colours . '">'
                . '<td class="px-3 py-2 whitespace-nowrap"><a class="' . self::LINK_CLASSES . '" href="' . $path . '?d=' . $date . '">' . $date . '</a></td>'
                . '<td class="px-3 py-2 text-slate-700">' . htmlspecialchars(ProofReport::HOUSES[$day['major']]) . '</td>'
                . '<td class="px-3 py-2"><span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold ' . $pill . '">' . $pill_icon . $status . '</span></td>'
                . '<td class="px-3 py-2 text-right tabular-nums">' . $day['proof'] . '</td>'
                . '<td class="px-3 py-2 text-right tabular-nums">' . $day['final'] . '</td>'
                . '<td class="px-3 py-2 text-right tabular-nums">' . $day['unknown'] . '</td>'
                . '<td class="px-3 py-2 text-right tabular-nums">' . self::errorsLink($day) . '</td>'
                . '<td class="px-3 py-2 text-slate-600 whitespace-nowrap tabular-nums">' . htmlspecialchars($day['last_changed'] ?? '') . '</td></tr>';
        }
        return $html . '</tbody></table></div>';
    }

}
