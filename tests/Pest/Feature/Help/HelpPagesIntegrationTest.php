<?php

/**
 * @file
 * The help pages, and how well they read with a screen reader: headings, links, images and forms.
 */

require_once __DIR__ . '/../../../bootstrap.php';
require_once __DIR__ . '/../../Support/MemberPageStubs.php';

use Symfony\Component\HttpFoundation\Response;

uses(TransactionalTestCase::class);

defined('CONTACTEMAIL') || define('CONTACTEMAIL', 'contact@example.test');
defined('DOMAIN') || define('DOMAIN', 'example.test');
defined('WEBPATH') || define('WEBPATH', '/');

/**
 * The response to a visit.
 */
function help_get(string $path): Response {
    return MemberPageStubs::get($path);
}

/**
 * A visit as a document, to look through for what a screen reader would meet.
 */
function help_dom(string $path): DOMDocument {
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="utf-8"?>' . help_get($path)->getContent());
    libxml_clear_errors();

    return $dom;
}

/**
 * Nodes matching a query, leaving out anything inside the sample box on Link to us, which is hidden from everyone.
 *
 * @return list<\DOMNode>
 *   The nodes.
 */
function help_nodes(string $path, string $query): array {
    $found = (new DOMXPath(help_dom($path)))->query($query . '[not(ancestor-or-self::*[@inert])]');

    return $found === false ? [] : iterator_to_array($found);
}

dataset('help_pages', [
    'about' => ['/about', 'about', 'About us'],
    'help' => ['/help', 'help', 'Answers to your questions'],
    'link to us' => ['/help/linktous', 'linktous', 'Link to us'],
    'contact' => ['/contact', 'contact', 'Contact OpenAustralia.org'],
    'get involved' => ['/getinvolved', 'getinvolved', 'Contribute to OpenAustralia.org'],
    'house rules' => ['/houserules', 'houserules', 'House rules'],
    'privacy' => ['/privacy', 'privacy', 'Privacy Policy'],
]);

beforeEach(function () {
    MemberPageStubs::bind();
});

afterEach(function () {
    MemberPageStubs::restoreVisitor();
    laravel_spec_new_request();
});

describe('each help page', function () {
    it('answers 200', function (string $path) {
        expect(help_get($path)->getStatusCode())->toBe(200);
    })->with('help_pages');

    it('answers with a trailing slash', function (string $path) {
        expect(help_get("$path/")->getStatusCode())->toBe(200);
    })->with('help_pages');

    it('sets the title and menu', function (string $path, string $page) {
        expect(help_get($path)->getContent())->toContain("[top:$page:]");
    })->with('help_pages');

    it('has the page title as its one h1', function (string $path, string $page, string $title) {
        expect(array_map(fn (DOMNode $h): string => trim($h->textContent), help_nodes($path, '//h1')))->toBe([$title]);
    })->with('help_pages');

    it('skips no heading level', function (string $path) {
        $levels = array_map(fn (DOMNode $h): int => (int) substr($h->nodeName, 1), help_nodes($path, '//*[self::h1 or self::h2 or self::h3 or self::h4 or self::h5 or self::h6]'));
        $jumps = array_filter($levels, fn (int $level, int $i): bool => $i > 0 && $level > $levels[$i - 1] + 1, ARRAY_FILTER_USE_BOTH);

        expect($jumps)->toBe([]);
    })->with('help_pages');

    it('uses no table for layout', function (string $path) {
        expect(help_nodes($path, '//table'))->toBe([]);
    })->with('help_pages');

    it('gives every image alt text', function (string $path) {
        expect(help_nodes($path, '//img[not(@alt)]'))->toBe([]);
    })->with('help_pages');

    it('names every link', function (string $path) {
        $unnamed = array_filter(help_nodes($path, '//a[@href]'), fn (DOMNode $a): bool => trim($a->textContent) === '' && !$a->attributes->getNamedItem('aria-label'));

        expect($unnamed)->toBe([]);
    })->with('help_pages');

    it('points every anchor at something', function (string $path) {
        $ids = array_map(fn (DOMNode $n): string => $n->getAttribute('id'), help_nodes($path, '//*[@id]'));
        $missing = array_filter(
            array_map(fn (DOMNode $a): string => ltrim($a->getAttribute('href'), '#'), help_nodes($path, '//a[starts-with(@href, "#")]')),
            fn (string $id): bool => !in_array($id, $ids, true)
        );

        expect($missing)->toBe([]);
    })->with('help_pages');

    it('uses no positive tabindex', function (string $path) {
        expect(help_nodes($path, '//*[@tabindex > 0]'))->toBe([]);
    })->with('help_pages');

    it('labels every field', function (string $path) {
        $ids = array_map(fn (DOMNode $l): string => $l->getAttribute('for'), help_nodes($path, '//label[@for]'));
        $unlabelled = array_filter(help_nodes($path, '//textarea | //input[not(@type="hidden" or @type="submit")]'), fn (DOMNode $f): bool => !in_array($f->getAttribute('id'), $ids, true));

        expect($unlabelled)->toBe([]);
    })->with('help_pages');
});

describe('the help answers', function () {
    it('say Plausible, not Google Analytics', function () {
        expect(help_get('/help')->getContent())->toContain('Plausible')->not->toContain('urchin');
    });

    it('keep the old anchors working', function (string $id) {
        expect(help_nodes('/help', "//h2[@id=\"$id\"]"))->toHaveCount(1);
    })->with(['missing', 'glossary', 'privacy', 'cookie', 'extreme', 'rss', 'votingrecord', 'numbers', 'api', 'regmem', 'regmem-links']);

    it('tell people a link opens a new tab', function () {
        expect(help_get('/help')->getContent())->toContain('Plausible<span class="sr-only"> (opens in a new tab)</span>');
    });

    it('list their questions in a labelled nav', function () {
        expect(help_nodes('/help', '//nav[@aria-label="Questions on this page"]'))->toHaveCount(1);
    });
});

describe('the Privacy Policy', function () {
    it('lists three promises', function () {
        expect(help_nodes('/privacy', '//ol/li'))->toHaveCount(3);
    });

    it('gives the help answer the same words', function () {
        $items = fn (string $path): array => array_map(fn (DOMNode $li): string => rtrim(trim($li->textContent), '.'), help_nodes($path, '//ol/li'));

        expect($items('/help'))->toBe($items('/privacy'));
    });
});

describe('the Contact page', function () {
    it('hides the address from scrapers', function () {
        expect(help_get('/contact')->getContent())->toContain('contact&#64;example.test');
    });
});

describe('the Link to us page', function () {
    it('labels the code field', function () {
        expect(help_nodes('/help/linktous', '//label[@for="embed-code"]'))->toHaveCount(1);
    });

    it('makes the sample box inert', function () {
        expect((new DOMXPath(help_dom('/help/linktous')))->query('//figure/div[@inert]')->length)->toBe(1);
    });

    it('describes the sample box', function () {
        expect(help_nodes('/help/linktous', '//figcaption[contains(@class, "sr-only")]'))->toHaveCount(1);
    });

    it('gives the code with this site\'s address', function () {
        expect(help_get('/help/linktous')->getContent())->toContain("action='https://example.test/search/'");
    });
});

describe('the search tips', function () {
    it('show the example searches', function () {
        expect(view('help.search-tips')->render())->toContain('<code>"Cole Inquiry"</code>');
    });
});

describe('the help answers with an email link', function () {
    it('use the contact address', function () {
        expect(help_get('/help')->getContent())->toContain('mailto:contact&#64;example.test')->and(substr_count(help_get('/help')->getContent(), 'mailto:'))->toBe(2);
    });
});
