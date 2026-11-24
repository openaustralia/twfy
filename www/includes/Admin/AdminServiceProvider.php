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
        foreach (glob(dirname(__DIR__, 2) . '/resources/views/components/admin/*.blade.php') as $file) {
            $component = basename($file, '.blade.php');
            $this->app->make('blade.compiler')->component('components.admin.' . $component, 'admin.' . $component);
        }
    }

}
