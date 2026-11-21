<?php

namespace OpenAustralia\TWFY;

/**
 * The pieces the admin proof report pages share: the coloured segments, the bars and the links.
 */
class ProofReportView {

    public const LINK_CLASSES = '!text-teal-700 !no-underline hover:!underline';

    /**
     * The segments of a bar, in the order they are drawn. The colours are checked for colour-blind separation.
     */
    private const SEGMENTS = [
        'final' => ['label' => 'Final', 'bar' => 'bg-[#0d9488]', 'pill' => 'bg-teal-100 text-teal-800'],
        'proof' => ['label' => 'Proof', 'bar' => 'bg-[#d97706]', 'pill' => 'bg-amber-100 text-amber-800'],
        'mixed' => ['label' => 'Mixed', 'bar' => 'bg-[#e11d48]', 'pill' => 'bg-rose-100 text-rose-800'],
        'unknown' => ['label' => 'Unknown', 'bar' => 'bg-[#7c6fd0]', 'pill' => 'bg-violet-100 text-violet-800'],
    ];

    /**
     * The segments to draw for some rows: Mixed only when one of them has a mixed day.
     *
     * @param list<array<string, mixed>> $rows
     *   Rows that each have a "mixed" count.
     *
     * @return array<string, array{label: string, bar: string, pill: string}>
     *   The segments, keyed by the row key that holds their count.
     */
    public static function segments(array $rows): array {
        $segments = self::SEGMENTS;
        if (!array_filter($rows, fn (array $row): bool => $row['mixed'] > 0)) {
            unset($segments['mixed']);
        }
        return $segments;
    }

    /**
     * The key of colours, one entry for each segment.
     *
     * @param array<string, array{label: string, bar: string, pill: string}> $segments
     *   The segments from segments().
     */
    public static function legend(array $segments): string {
        $html = '<ul class="flex flex-wrap gap-x-5 gap-y-1 my-4 p-0 list-none text-sm text-slate-700">';
        foreach ($segments as $segment) {
            $html .= '<li class="flex items-center gap-2"><span class="inline-block w-3 h-3 rounded-sm ' . $segment['bar'] . '"></span>' . $segment['label'] . '</li>';
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
            return '<div class="flex flex-1 h-3 rounded bg-slate-100" role="img" aria-label="' . htmlspecialchars($name) . ': no days"></div>';
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
     * A link to one of the report's pages, with only the parameters that have a value.
     *
     * @param string $page
     *   The file name in the admin folder, such as proof_year.php.
     * @param array<string, int|string|null> $params
     *   The query parameters.
     */
    public static function link(string $page, array $params = []): string {
        $query = http_build_query(array_filter($params, fn ($value): bool => $value !== null && $value !== ''));
        return htmlspecialchars('/admin/' . $page . ($query === '' ? '' : '?' . $query));
    }

    /**
     * The breadcrumb trail, with the last item as the page you are on.
     *
     * @param list<array{string, string|null}> $trail
     *   A label and a link for each step. The link of the last step is ignored.
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
        return number_format($count) . ($count === 1 ? ' day' : ' days');
    }

    /**
     * The name of a month, from 1 to 12.
     */
    public static function monthName(int $month): string {
        return date('F', mktime(0, 0, 0, $month, 1));
    }

}
