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

    protected function tearDown(): void {
        $_GET = [];
        $_POST = [];
        unset($_SERVER['REQUEST_METHOD'], $GLOBALS['THEUSER']);
        parent::tearDown();
    }

    /**
     * Stands in for the signed-in visitor, who passes the admin check.
     *
     * @param string $status
     *   Their role, such as Superuser.
     * @param int $user_id
     *   Their user id.
     */
    protected function signInAs(string $status, int $user_id): void {
        $GLOBALS['THEUSER'] = new class($status, $user_id) {

            public function __construct(private string $status, private int $user_id) {
            }

            public function isloggedin(): bool {
                return true;
            }

            public function is_able_to(string $action): bool {
                return true;
            }

            public function status(): string {
                return $this->status;
            }

            public function user_id(): int {
                return $this->user_id;
            }

            public function password(): string {
                return 'password-hash';
            }

        };
    }

    /**
     * The HTML a controller action prints.
     */
    public function render(Closure $action): string {
        ob_start();
        $action();
        return ob_get_clean();
    }

}
