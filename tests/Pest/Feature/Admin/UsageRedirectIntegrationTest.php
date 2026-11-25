<?php

/**
 * @file
 * The old usage report address, which now sends visitors to the Busiest API keys report.
 */

require_once __DIR__ . '/../../../PageRenderingIntegrationTestCase.php';

uses(PageRenderingIntegrationTestCase::class);

describe('/admin/usage.php', function () {
    it('answers 301', function () {
        expect($this->renderPage(__DIR__ . '/../../../../www/docs/admin/usage.php', 'GET', ['period' => '7'])['status'])->toBe(301);
    });

    it('draws nothing', function () {
        expect($this->renderPage(__DIR__ . '/../../../../www/docs/admin/usage.php')['output'])->toBe('');
    });
});
