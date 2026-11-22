<?php

/**
 * @file
 * The admin usage page's period and table rows. Uses fictional people,
 * documentation IP addresses and made-up paths.
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use OpenAustralia\TWFY\AdminView;
use OpenAustralia\TWFY\UsageStats\UsagePage;

/**
 * A fictional key holder.
 */
function usage_page_spec_user(string $url = ''): object {
    return (object) ['firstname' => 'Ada', 'lastname' => '<Example>', 'email' => 'ada@example.test', 'url' => $url];
}

/**
 * One of ApiUsageReport::topKeys()'s rows.
 *
 * @return array<string, mixed>
 *   The row.
 */
function usage_page_spec_key_row(?object $user, bool $commercial = false): array {
    return [
        'key' => (object) ['user' => $user, 'reason' => "A school\n project", 'commercial' => $commercial, 'disabled' => null],
        'total' => 12345,
        'mean' => 411.5,
        'min' => 3,
        'max' => 2000,
    ];
}

describe('UsagePage::period()', function () {
    it('accepts an offered period', function (string $requested, int $period) {
        expect(UsagePage::period($requested))->toBe($period);
    })->with([['30', 30], ['7', 7], ['1', 1]]);

    it('falls back to 30 days', function (string $requested) {
        expect(UsagePage::period($requested))->toBe(30);
    })->with(['', '2', 'banana']);
});

describe('UsagePage::range()', function () {
    it('ends today', function () {
        expect(UsagePage::range(7, new DateTimeImmutable('2026-03-10'))[1])->toBe('2026-03-10');
    });

    it('starts period - 1 days before', function () {
        expect(UsagePage::range(7, new DateTimeImmutable('2026-03-10'))[0])->toBe('2026-03-04');
    });

    describe('for today only', function () {
        it('starts today', function () {
            expect(UsagePage::range(1, new DateTimeImmutable('2026-03-10'))[0])->toBe('2026-03-10');
        });
    });
});

describe('UsagePage::name()', function () {
    describe('without an account', function () {
        it('says so', function () {
            expect(UsagePage::name(null))->toBe('<em>No account</em>');
        });
    });

    describe('without a web address', function () {
        it('escapes the name', function () {
            expect(UsagePage::name(usage_page_spec_user()))->toBe('Ada &lt;Example&gt;');
        });
    });

    describe('with an http(s) web address', function () {
        it('links the name', function () {
            expect(UsagePage::name(usage_page_spec_user('https://example.test/?a=1&b=2')))
              ->toBe('<a class="' . AdminView::LINK_CLASSES . '" href="https://example.test/?a=1&amp;b=2">Ada &lt;Example&gt;</a>');
        });
    });

    describe('with another kind of address', function () {
        it('does not link it', function () {
            expect(UsagePage::name(usage_page_spec_user('javascript:alert(1)')))->toBe('Ada &lt;Example&gt;');
        });
    });
});

describe('UsagePage::keyRows()', function () {
    it('formats a key', function () {
        expect(UsagePage::keyRows([usage_page_spec_key_row(usage_page_spec_user())])[0])->toBe([
            'Ada &lt;Example&gt;',
            'ada@example.test',
            '<span title="A school project">A school project</span>',
            'No',
            '',
            '12,345',
            '411.5',
            '3',
            '2,000',
        ]);
    });

    it('marks commercial keys', function () {
        expect(UsagePage::keyRows([usage_page_spec_key_row(usage_page_spec_user(), true)])[0][3])->toBe('Yes');
    });

    describe('without an account', function () {
        it('leaves the email empty', function () {
            expect(UsagePage::keyRows([usage_page_spec_key_row(null)])[0][1])->toBe('');
        });
    });
});

describe('UsagePage::dailyRows()', function () {
    it('formats a day', function () {
        expect(UsagePage::dailyRows([['day' => '2026-03-10', 'downloads' => 1500, 'internal' => 2]]))
          ->toBe([['2026-03-10', '1,500', '2']]);
    });
});

describe('UsagePage::pathRows()', function () {
    it('escapes the path', function () {
        expect(UsagePage::pathRows([['path' => '/a&b.xml', 'downloads' => 1000]]))
          ->toBe([['/a&amp;b.xml', '1,000']]);
    });
});

describe('UsagePage::visitorRows()', function () {
    it('formats a visitor', function () {
        expect(UsagePage::visitorRows([['ip_address' => '203.0.113.5', 'user_agent' => '<Bot>', 'downloads' => 7, 'internal' => true]]))
          ->toBe([['203.0.113.5', '&lt;Bot&gt;', '7', 'Yes']]);
    });

    describe('from outside OAF', function () {
        it('says no', function () {
            expect(UsagePage::visitorRows([['ip_address' => '203.0.113.5', 'user_agent' => '', 'downloads' => 7, 'internal' => false]])[0][3])
              ->toBe('No');
        });
    });
});

describe('UsagePage::periodLinks()', function () {
    it('marks the current period', function () {
        expect(UsagePage::periodLinks(7))->toContain('<li class="!ml-0 text-slate-900" aria-current="page">Last 7 days</li>');
    });

    it('links the other periods', function () {
        expect(UsagePage::periodLinks(7))->toContain('href="/admin/usage.php?period=1">Today</a>');
    });
});
