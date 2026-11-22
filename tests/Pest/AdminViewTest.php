<?php

/**
 * @file
 * The look shared by the admin pages.
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use OpenAustralia\TWFY\AdminView;

describe('AdminView::link()', function () {
    it('leaves out parameters with no value', function () {
        expect(AdminView::link('proof_year.php', ['year' => 2026, 'month' => null, 'extra' => '']))->toBe('/admin/proof_year.php?year=2026');
    });

    it('keeps several parameters', function () {
        expect(AdminView::link('proof_year.php', ['year' => 2026, 'month' => 3]))->toBe('/admin/proof_year.php?year=2026&month=3');
    });

    it('adds no query when there are none', function () {
        expect(AdminView::link('proof.php'))->toBe('/admin/proof.php');
    });
});

describe('AdminView::breadcrumbs()', function () {
    beforeEach(function () {
        $this->html = AdminView::breadcrumbs([
            ['Admin', '/admin/'],
            ['Year', AdminView::link('proof_year.php', ['year' => 2025, 'month' => 3])],
            ['March', null],
        ]);
    });

    it('links every step but the last', function () {
        expect($this->html)->toContain('href="/admin/"');
    });

    it('marks the last step as the current page', function () {
        expect($this->html)->toContain('aria-current="page">March');
    });

    it('escapes an address once', function () {
        expect($this->html)->toContain('year=2025&amp;month=3"');
    });

    it('does not escape an address twice', function () {
        expect($this->html)->not->toContain('&amp;amp;');
    });
});

describe('AdminView::table()', function () {
    describe('without rows', function () {
        describe('with a note', function () {
            it('says so', function () {
                expect(AdminView::table(['Path'], [], 'Nothing & more'))->toBe('<p class="text-sm text-slate-600">Nothing &amp; more</p>');
            });
        });

        describe('without a note', function () {
            it('draws the headings only', function () {
                expect(AdminView::table(['Path'], []))->toContain('<tbody></tbody>');
            });
        });
    });

    describe('with rows', function () {
        beforeEach(function () {
            $this->html = AdminView::table(['Path', '<a href="?o=c">Downloads</a>'], [['/a.xml', '1,000']], '', [1]);
        });

        it('raises the shared table on a card', function () {
            expect($this->html)->toContain(AdminView::TABLE_WRAPPER . AdminView::TABLE . AdminView::HEADING_ROW);
        });

        it('keeps HTML in headings', function () {
            expect($this->html)->toContain('<th class="px-3 py-2 font-semibold text-right"><a href="?o=c">Downloads</a></th>');
        });

        it('left-aligns text cells', function () {
            expect($this->html)->toContain('<td class="px-3 py-2">/a.xml</td>');
        });

        it('right-aligns number cells', function () {
            expect($this->html)->toContain('<td class="px-3 py-2 text-right tabular-nums">1,000</td>');
        });

        it('stripes and divides the rows', function () {
            expect($this->html)->toContain(AdminView::ROW);
        });
    });
});

describe('AdminView::numericColumns()', function () {
    it('finds the number columns', function () {
        expect(AdminView::numericColumns([
            ['<a href="/u/1">Ada</a>', '1,234', '12%', '4.5', ''],
            ['Bob', '7', '0%', '', '2026-03-10'],
        ]))->toBe([1, 2, 3]);
    });

    it('ignores empty cells', function () {
        expect(AdminView::numericColumns([['', '3'], ['', '']]))->toBe([1]);
    });

    it('treats a dotted address as text', function () {
        expect(AdminView::numericColumns([['203.0.113.5']]))->toBe([]);
    });

    describe('without rows', function () {
        it('finds none', function () {
            expect(AdminView::numericColumns([]))->toBe([]);
        });
    });
});
