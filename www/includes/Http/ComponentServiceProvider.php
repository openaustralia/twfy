<?php

namespace OpenAustralia\TWFY\Http;

use Illuminate\Support\ServiceProvider;

/**
 * Names the Blade components, so Blade doesn't guess their classes from composer.json.
 */
class ComponentServiceProvider extends ServiceProvider {

    /**
     * Registers <x-admin.cell>, <x-members.card> and the rest of resources/views/components, one folder deep.
     */
    public function boot(): void {
        foreach (glob(dirname(__DIR__, 2) . '/resources/views/components/*/*.blade.php') as $file) {
            $name = basename(dirname($file)) . '.' . basename($file, '.blade.php');
            $this->app->make('blade.compiler')->component('components.' . $name, $name);
        }
    }

}
