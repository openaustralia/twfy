<?php

namespace OpenAustralia\TWFY\Admin;

use Illuminate\Support\ServiceProvider;

/**
 * Names the admin Blade components, so Blade doesn't guess their classes from composer.json.
 */
class AdminServiceProvider extends ServiceProvider {

    /**
     * Registers <x-admin.cell>, <x-admin.heading> and the other components in resources/views/components/admin.
     */
    public function boot(): void {
        foreach (['cell', 'heading', 'link', 'row', 'table'] as $component) {
            $this->app->make('blade.compiler')->component('components.admin.' . $component, 'admin.' . $component);
        }
    }

}
