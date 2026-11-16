<?php

/**
 * @file
 */

require_once __DIR__ . '/../../www/includes/easyparliament/search.php';

describe('highlighted_html()', function () {
    describe('when the search term is empty', function () {
        it('escapes the text', function () {
            expect(highlighted_html('<script>alert("x")</script> Test', ''))
              ->toBe('&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt; Test');
        });
    });

    describe('when the text has matches', function () {
        it('highlights them, ignoring case', function () {
            expect(highlighted_html('Canberra CANBERRA', 'canberra'))
              ->toBe('<span class="hi">Canberra</span> <span class="hi">CANBERRA</span>');
        });
    });

    describe('when the term has regex characters', function () {
        it('matches them literally', function () {
            expect(highlighted_html('<b>A+B?</b>', 'A+B?'))
              ->toBe('&lt;b&gt;<span class="hi">A+B?</span>&lt;/b&gt;');
        });
    });

    describe('when nothing matches', function () {
        it('returns the escaped text', function () {
            expect(highlighted_html('Parliament House', 'senate'))
              ->toBe('Parliament House');
        });
    });
});
