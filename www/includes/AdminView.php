<?php

namespace OpenAustralia\TWFY;

/**
 * The look shared by the admin pages: links, breadcrumbs and tables.
 *
 * The import status report set this style. Every method returns HTML.
 */
class AdminView {

    public const LINK_CLASSES = '!text-teal-700 !no-underline hover:!underline';

    // A raised card, as in Material Design, that scrolls sideways when the table is wider than the page.
    public const TABLE_WRAPPER = '<div class="max-w-4xl overflow-x-auto rounded-lg bg-white shadow-md">';

    public const TABLE = '<table class="w-full !m-0 text-sm border-collapse">';

    public const HEADING_ROW = '<thead><tr class="bg-slate-100 text-left text-xs uppercase tracking-wide text-slate-600">';

    public const ROW = '<tr class="border-0 border-b border-solid border-slate-200 even:bg-slate-50 hover:bg-teal-50">';

    public const HEADING_CLASSES = 'mt-8 mb-4 mx-0 text-base font-semibold text-slate-900';

    public const NOTE_CLASSES = 'text-sm text-slate-600';

    public const NUMBER_CLASSES = 'text-right tabular-nums';

    /**
     * The address of an admin page, with only the parameters that have a value. Not escaped for HTML.
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
     * A row of links that choose what a page shows, with the current choice marked and not linked.
     *
     * @param list<array{string, string, bool}> $choices
     *   Each choice's label, its address (not escaped), and whether it is the current one.
     */
    public static function filterLinks(array $choices): string {
        $html = '<ul class="flex flex-wrap items-center gap-6 !m-0 p-0 list-none text-sm font-semibold">';
        foreach ($choices as [$label, $url, $current]) {
            $label = htmlspecialchars($label);
            $html .= $current
                ? '<li class="!ml-0 text-slate-900" aria-current="page">' . $label . '</li>'
                : '<li class="!ml-0"><a class="' . self::LINK_CLASSES . '" href="' . htmlspecialchars($url) . '">' . $label . '</a></li>';
        }
        return $html . '</ul>';
    }

    /**
     * A table, or a note when there are no rows and the caller gave one.
     *
     * @param list<string> $headings
     *   The column headings, as HTML.
     * @param array<int, array<int|string, string|int|float|null>> $rows
     *   The cells, as HTML, in column order.
     * @param string $empty
     *   What to say when there are no rows. With none, the table is drawn with its headings only.
     * @param list<int> $numeric
     *   The columns that hold numbers, which are right-aligned.
     */
    public static function table(array $headings, array $rows, string $empty = '', array $numeric = []): string {
        if (!$rows && $empty !== '') {
            return '<p class="' . self::NOTE_CLASSES . '">' . htmlspecialchars($empty) . '</p>';
        }
        $html = self::TABLE_WRAPPER . self::TABLE . self::HEADING_ROW . self::headings($headings, $numeric) . '</tr></thead><tbody>';
        foreach ($rows as $row) {
            $html .= self::ROW;
            foreach (array_values($row) as $index => $cell) {
                $html .= self::td((string) $cell, in_array($index, $numeric, true) ? self::NUMBER_CLASSES : '');
            }
            $html .= '</tr>';
        }
        return $html . '</tbody></table></div>';
    }

    /**
     * A heading cell for each column.
     *
     * @param list<string> $headings
     *   The headings, as HTML.
     * @param list<int> $numeric
     *   The columns that hold numbers, whose headings are right-aligned.
     */
    public static function headings(array $headings, array $numeric = []): string {
        $html = '';
        foreach (array_values($headings) as $index => $heading) {
            $html .= self::th($heading, in_array($index, $numeric, true) ? 'text-right' : '');
        }
        return $html;
    }

    /**
     * A heading cell.
     *
     * @param string $html
     *   The heading, as HTML.
     * @param string $classes
     *   More classes for the cell.
     */
    public static function th(string $html, string $classes = ''): string {
        return '<th class="px-3 py-2 font-semibold' . ($classes === '' ? '' : ' ' . $classes) . '">' . $html . '</th>';
    }

    /**
     * A body cell.
     *
     * @param string $html
     *   The cell, as HTML.
     * @param string $classes
     *   More classes for the cell.
     */
    public static function td(string $html, string $classes = ''): string {
        return '<td class="px-3 py-2' . ($classes === '' ? '' : ' ' . $classes) . '">' . $html . '</td>';
    }

    /**
     * The columns in which every filled cell is a number, such as 1,234, 4.5 or 12%.
     *
     * @param array<int, array<int|string, string|int|float|null>> $rows
     *   The cells, as HTML.
     *
     * @return list<int>
     *   The column indexes.
     */
    public static function numericColumns(array $rows): array {
        $numeric = null;
        foreach ($rows as $row) {
            foreach (array_values($row) as $index => $cell) {
                $text = trim(strip_tags((string) $cell));
                if ($text !== '' && !is_numeric(str_replace([',', '%'], '', $text))) {
                    $numeric[$index] = false;
                } elseif ($text !== '' && !isset($numeric[$index])) {
                    $numeric[$index] = true;
                }
            }
        }
        return array_keys(array_filter($numeric ?? []));
    }

}
