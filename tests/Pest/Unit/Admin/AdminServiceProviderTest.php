<?php

/**
 * @file
 * The admin Blade components are named, so Blade never guesses their classes from composer.json.
 */

require_once __DIR__ . '/../../../bootstrap.php';

use OpenAustralia\TWFY\Admin\AdminServiceProvider;

describe('AdminServiceProvider::boot()', function () {
    beforeEach(function () {
        $this->blade = twfy_laravel()->make('blade.compiler');
        (new AdminServiceProvider(twfy_laravel()))->boot();
    });

    it('names each admin component', function (string $component) {
        expect($this->blade->getClassComponentAliases())->toHaveKey('admin.' . $component, 'components.admin.' . $component);
    })->with(['cell', 'heading', 'link', 'row', 'table']);
});
