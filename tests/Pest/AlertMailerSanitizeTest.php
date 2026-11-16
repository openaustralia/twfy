<?php

/**
 * @file
 */

require_once __DIR__ . '/../../www/includes/alertmailer_sanitize.php';

describe('sanitizeAlertCriteria()', function () {
    describe('with an unknown prefix', function () {
        it('removes its colon', function (string $criteria, string $expected) {
            expect(sanitizeAlertCriteria($criteria))->toBe($expected);
        })->with([
            'on its own' => [
                'Higher Education; Training: Mature Aged Workers; ANU;',
                'Higher Education; Training Mature Aged Workers; ANU;',
            ],
            'beside known prefixes' => [
                'topic:health speaker:10515 custom_field:value section:wrans',
                'topic health speaker:10515 custom_field value section:wrans',
            ],
        ]);
    });

    describe('with known prefixes or URLs', function () {
        it('leaves the text unchanged', function (string $criteria) {
            expect(sanitizeAlertCriteria($criteria))->toBe($criteria);
        })->with([
            'known prefixes' => 'speaker:10973 section:debate batch:2171 date:2026-05-10',
            'known prefixes in any case' => 'Speaker:10973 GROUPBY:day Bias:1:86400',
            'URLs' => 'see http://example.com/path and https://example.org',
        ]);
    });
});
