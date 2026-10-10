<?php

/**
 * @file
 * Page-level tests for /alert/unsubscribe/. The helper is tested in
 * AlertUnsubscribeIntegrationTest. These run index.php itself, because the
 * promise that matters is that a GET, which mail scanners and link previews
 * send, never removes an alert.
 */

require_once __DIR__ . '/PageRenderingIntegrationTestCase.php';

/**
 * Integration tests for the one-click unsubscribe page.
 */
class AlertUnsubscribePageIntegrationTest extends PageRenderingIntegrationTestCase {

    private const PAGE = __DIR__ . '/../www/docs/alert/unsubscribe/index.php';

    private const EMAIL = 'unsubscribe.page.test@example.invalid';

    /**
     * @var int[]
     */
    private array $alertIds = [];

    /**
     * The page runs in another process against the committed database, so the
     * rows are real and tearDown() has to remove them.
     */
    private function insertAlert(string $criteria, string $token): int {
        $q = parlDBQuery(
            "INSERT INTO alerts (email, criteria, registrationtoken, confirmed, deleted, created) VALUES (?, ?, ?, 1, 0, NOW())",
            self::EMAIL,
            $criteria,
            $token
        );
        $id = (int) $q->insert_id();
        $this->alertIds[] = $id;
        return $id;
    }

    /**
     *
     */
    private function isDeleted(int $id): bool {
        return (int) parlDBQuery('SELECT deleted FROM alerts WHERE alert_id = ?', $id)->field(0, 'deleted') === 1;
    }

    /**
     *
     */
    protected function tearDown(): void {
        parlDBQuery('DELETE FROM alerts WHERE email = ?', self::EMAIL);
        $this->alertIds = [];
        parent::tearDown();
    }

    /**
     * What a link preview or mail scanner does.
     */
    public function test_a_get_asks_first_and_leaves_every_alert_active(): void {
        $a = $this->insertAlert('speaker:1', 'pagetokena');
        $b = $this->insertAlert('climate', 'pagetokenb');

        $page = $this->renderPage(self::PAGE, 'GET', ['t' => $a . '-pagetokena']);

        $this->assertSame(0, $page['exit'], $page['stderr']);
        $this->assertSame(200, $page['status']);
        $this->assertStringContainsString('Unsubscribe from all alerts', $page['output']);
        $this->assertFalse($this->isDeleted($a));
        $this->assertFalse($this->isDeleted($b));
    }

    /**
     * What a mail app's Unsubscribe button sends.
     */
    public function test_a_post_removes_every_alert_at_the_address(): void {
        $a = $this->insertAlert('speaker:1', 'pagetokena');
        $b = $this->insertAlert('climate', 'pagetokenb');

        $page = $this->renderPage(self::PAGE, 'POST', ['t' => $a . '-pagetokena']);

        $this->assertSame(0, $page['exit'], $page['stderr']);
        $this->assertSame(200, $page['status']);
        $this->assertStringContainsString('Your alerts have been unsubscribed', $page['output']);
        $this->assertTrue($this->isDeleted($a));
        $this->assertTrue($this->isDeleted($b));
    }

    /**
     *
     */
    public function test_an_invalid_token_gets_400_on_get_and_post_and_removes_nothing(): void {
        $a = $this->insertAlert('speaker:1', 'pagetokena');

        foreach (['GET', 'POST'] as $method) {
            $page = $this->renderPage(self::PAGE, $method, ['t' => $a . '-wrongtoken']);

            $this->assertSame(0, $page['exit'], $page['stderr']);
            $this->assertSame(400, $page['status'], $method);
            $this->assertStringContainsString('appears to be incomplete', $page['output']);
        }
        $this->assertFalse($this->isDeleted($a));
    }

    /**
     * /alert/unsubscribe/?t[]=bad puts an array in the token.
     */
    public function test_a_token_that_is_not_a_string_gets_400_not_an_error(): void {
        $a = $this->insertAlert('speaker:1', 'pagetokena');

        foreach (['GET', 'POST'] as $method) {
            $page = $this->renderPage(self::PAGE, $method, ['t' => ['bad']]);

            $this->assertSame(0, $page['exit'], $page['stderr']);
            $this->assertSame(400, $page['status'], $method);
        }
        $this->assertFalse($this->isDeleted($a));
    }

}
