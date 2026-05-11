<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../www/includes/alertmailer_sanitize.php';

/**
 * Tests for sanitizeAlertCriteria.
 */
class AlertMailerSanitizeTest extends TestCase {

    /**
     * Unknown prefixes should be converted to plain words.
     */
    public function test_unknown_prefix_colon_removed(): void {
        $criteria = 'Higher Education; Training: Mature Aged Workers; ANU;';
        $result = sanitizeAlertCriteria($criteria);

        $this->assertSame('Higher Education; Training Mature Aged Workers; ANU;', $result);
    }

    /**
     * Known prefixes should be preserved.
     */
    public function test_known_prefixes_preserved(): void {
        $criteria = 'speaker:10973 section:debate batch:2171 date:2026-05-10';
        $result = sanitizeAlertCriteria($criteria);

        $this->assertSame($criteria, $result);
    }

    /**
     * Prefix matching should be case-insensitive for allowlisted prefixes.
     */
    public function test_known_prefix_case_insensitive(): void {
        $criteria = 'Speaker:10973 GROUPBY:day Bias:1:86400';
        $result = sanitizeAlertCriteria($criteria);

        $this->assertSame($criteria, $result);
    }

    /**
     * URLs should not be altered.
     */
    public function test_url_like_tokens_not_modified(): void {
        $criteria = 'see http://example.com/path and https://example.org';
        $result = sanitizeAlertCriteria($criteria);

        $this->assertSame($criteria, $result);
    }

    /**
     * Unknown and known prefixes can coexist.
     */
    public function test_mixed_prefixes_only_unknown_sanitized(): void {
        $criteria = 'topic:health speaker:10515 custom_field:value section:wrans';
        $result = sanitizeAlertCriteria($criteria);

        $this->assertSame('topic health speaker:10515 custom_field value section:wrans', $result);
    }

}
