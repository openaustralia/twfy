<?php

/**
 * @file
 * Like AdminControllerTestCase, but rows are committed, so the legacy connection can see them.
 */

require_once __DIR__ . '/AdminControllerTestCase.php';

/**
 * The legacy code and Eloquent use separate connections, and neither can see the other's open transaction.
 *
 * A spec that uses this class must delete what it adds.
 */
abstract class CommittedAdminControllerTestCase extends AdminControllerTestCase {

    protected function useMysqliTransaction(): bool {
        return false;
    }

    protected function useEloquentTransaction(): bool {
        return false;
    }

}
