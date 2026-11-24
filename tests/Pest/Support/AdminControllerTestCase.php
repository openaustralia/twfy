<?php

/**
 * @file
 */

require_once __DIR__ . '/../../bootstrap.php';

/**
 * Runs an admin controller as its page file does, inside the test's transaction.
 *
 * The legacy PAGE stands in for itself, as it would turn away a visitor who is not an admin. It prints each method
 * it is asked to run as [method], so a spec can see the layout drawn around the view.
 */
abstract class AdminControllerTestCase extends TransactionalTestCase {

    protected function setUp(): void {
        parent::setUp();
        twfy_laravel();
        $GLOBALS['PAGE'] = new class() {

            public function __call(string $name, array $args): string {
                echo "[$name]";
                return '';
            }

        };
    }

    /**
     * The HTML a controller action prints.
     */
    protected function render(Closure $action): string {
        ob_start();
        $action();
        return ob_get_clean();
    }

}
