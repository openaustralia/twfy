<?php

namespace OpenAustralia\TWFY\Http;

use Illuminate\Support\ServiceProvider;

/**
 * Names the Blade components, so Blade doesn't guess their classes from composer.json.
 */
class ComponentServiceProvider extends ServiceProvider {

    /**
     * Registers <x-icon>, <x-admin.cell>, <x-members.panel> and the rest of resources/views/components, at the top or
     * one folder deep.
     */
    public function boot(): void {
        $components = dirname(__DIR__, 2) . '/resources/views/components';
        foreach (array_merge(glob("$components/*.blade.php"), glob("$components/*/*.blade.php")) as $file) {
            $name = ltrim(str_replace('/', '.', substr($file, strlen($components), -strlen('.blade.php'))), '.');
            $this->app->make('blade.compiler')->component('components.' . $name, $name);
        }
    }

}
