<?php

/**
 * @file
 * The Blade components are named, so Blade never guesses their classes from composer.json.
 */

require_once __DIR__ . '/../../../bootstrap.php';

use OpenAustralia\TWFY\Http\ComponentServiceProvider;

describe('ComponentServiceProvider::boot()', function () {
    beforeEach(function () {
        $this->blade = twfy_laravel()->make('blade.compiler');
        (new ComponentServiceProvider(twfy_laravel()))->boot();
    });

    it('names each component', function (string $component) {
        expect($this->blade->getClassComponentAliases())->toHaveKey($component, 'components.' . $component);
    })->with(array_map(
        fn (string $file): string => basename(dirname($file)) . '.' . basename($file, '.blade.php'),
        glob(__DIR__ . '/../../../../www/resources/views/components/*/*.blade.php'),
    ));
});
