<?php

/**
 * @file
 */

describe('MySQL::sql_span_description()', function () {
    describe('with no leading word', function () {
        it('falls back to "query"', function () {
            $describe = fn (string $sql) => $this->sql_span_description($sql);

            expect($describe->call(new MySQL(), '(SELECT 1)'))->toBe('query');
        });
    });
});
