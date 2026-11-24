<?php

/**
 * @file
 */

use Illuminate\Foundation\Testing\Concerns\InteractsWithViews;
use PHPUnit\Framework\TestCase;

/**
 * Renders Blade views with Laravel's view assertions: $this->view('admin.alerts', $data)->assertSee(...).
 */
abstract class ViewTestCase extends TestCase {

    use InteractsWithViews;

    protected function setUp(): void {
        parent::setUp();
        twfy_laravel();
    }

}
