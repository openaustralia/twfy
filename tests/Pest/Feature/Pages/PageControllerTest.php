<?php

/**
 * @file
 * The layout a public page controller draws around a view.
 */

require_once __DIR__ . '/../../../bootstrap.php';
require_once __DIR__ . '/../../Support/AdminControllerTestCase.php';
require_once __DIR__ . '/../../../../www/includes/data.php';

use OpenAustralia\TWFY\Pages\PageController;

uses(AdminControllerTestCase::class);

beforeEach(function () {
    $GLOBALS['DATA'] = new DATA();
    twfy_laravel()->make('view')->addLocation(__DIR__ . '/../../Support/views');
    $this->controller = new class extends PageController {

        public function show(string $text): void {
            $this->page('glossary', fn () => $this->view('page-controller-spec', ['text' => $text]));
        }

    };
});

describe('PageController::page()', function () {
    it('draws the page around the view', function () {
        $html = $this->render(fn () => $this->controller->show('Hello'));

        expect($html)->toBe('[page_start]<div class="mx-4 pb-12 pt-6 md:mx-8"><p>Hello</p>' . "\n" . '</div>[page_end]');
    });

    it('draws no side stripe', function () {
        $html = $this->render(fn () => $this->controller->show('Hello'));

        expect($html)->not->toContain('stripe');
    });

    it('gives the page a viewport', function () {
        $this->render(fn () => $this->controller->show('Hello'));

        expect($GLOBALS['DATA']->page_metadata('glossary', 'responsive'))->toBeTrue();
    });

    it('escapes what the view shows', function () {
        $html = $this->render(fn () => $this->controller->show('<script>alert(1)</script>'));

        expect($html)->not->toContain('<script>');
    });

    it('sets the page key for the menu', function () {
        $this->render(fn () => $this->controller->show('Hello'));

        expect($GLOBALS['this_page'])->toBe('glossary');
    });
});
