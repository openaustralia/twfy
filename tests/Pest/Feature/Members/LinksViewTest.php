<?php

/**
 * @file
 * The list of links on a profile, drawn for both the Blade and the legacy page.
 */

require_once __DIR__ . '/../../../bootstrap.php';
require_once __DIR__ . '/../../Support/ViewTestCase.php';

uses(ViewTestCase::class);

$link = ['url' => 'https://example.test/a', 'text' => 'Results', 'source' => 'From ABC'];

describe('members.show.links', function () use ($link) {
    describe('with a class', function () use ($link) {
        it('puts it on the link', function () use ($link) {
            $this->view('members.show.links', ['links' => [$link], 'class' => 'underline'])
              ->assertSee('<a href="https://example.test/a" class="underline">', false);
        });
    });

    describe('without a class', function () use ($link) {
        it('leaves the link plain', function () use ($link) {
            $this->view('members.show.links', ['links' => [$link]])
              ->assertSee('<a href="https://example.test/a">', false);
        });
    });

    describe('with a source', function () use ($link) {
        it('names it', function () use ($link) {
            $this->view('members.show.links', ['links' => [$link]])->assertSee('(From ABC)');
        });
    });

    describe('with markup in the text', function () use ($link) {
        it('draws no script tag', function () use ($link) {
            $this->view('members.show.links', ['links' => [['text' => '<script>alert(1)</script>'] + $link]])
              ->assertDontSee('<script>', false);
        });
    });
});

describe('members.show.legacy-links', function () use ($link) {
    beforeEach(function () use ($link) {
        $this->view = $this->view('members.show.legacy-links', ['links' => [$link]]);
    });

    it('draws the block heading', function () {
        $this->view->assertSee('More useful links for this person');
    });

    it('lists the links', function () {
        $this->view->assertSee('Results');
    });
});
