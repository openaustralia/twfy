<?php

/**
 * @file
 * Unit tests for MEMBER::url().
 *
 * These tests set properties directly via reflection and require no database.
 * They rely on make_member_url() and member_full_name() from utility.php.
 */

use PHPUnit\Framework\TestCase;

if (!defined('DOMAIN')) {
    define('DOMAIN', 'example.org');
}

require_once INCLUDESPATH . 'utility.php';

if (!class_exists('URL')) {
    /**
     * Minimal URL stub for MEMBER::url() tests.
     */
    class URL {

        private string $page;

        public function __construct(string $page) {
            $this->page = $page;
        }

        /**
         *
         */
        public function insert(array $params): void {
        }

        /**
         *
         */
        public function generate(string $type = ''): string {
            return '/' . $this->page . '/';
        }

    }
}

/**
 *
 */
class MemberUrlTest extends TestCase {

    /**
     *
     */
private function makeMember(int $house, string $firstName, string $lastName, string $constituency, string $title = ''): MEMBER {
        $reflection = new \ReflectionClass(MEMBER::class);
        $member = $reflection->newInstanceWithoutConstructor();
        $member->house_disp = $house;
        $member->first_name = $firstName;
        $member->last_name = $lastName;
        $member->constituency = $constituency;
        $member->title = $title;
        $member->houses = [$house];
        return $member;
}

    // =========================================================================

    /**
     * Absolute URLs (default)
     */
    public function test_url_mp_absolute(): void {
        $member = $this->makeMember(HOUSE::REPRESENTATIVES, 'Jane', 'Smith', 'Springfield');
        $url = $member->url(true);
        $this->assertStringStartsWith('//' . DOMAIN, $url);
        $this->assertStringContainsString('/mp/', $url);
        $this->assertStringContainsString('jane_smith', $url);
        $this->assertStringContainsString('springfield', $url);
    }

    /**
     *
     */
public function test_url_peer_absolute(): void {
    $member = $this->makeMember(HOUSE::SENATE, 'John', 'Doe', 'Some Shire');
        $url = $member->url(true);
        $this->assertStringStartsWith('//' . DOMAIN, $url);
        $this->assertStringContainsString('/peer/', $url);
        $this->assertStringContainsString('john_doe', $url);
}

    // =========================================================================

    /**
     * Relative URLs
     */
    public function test_url_mp_relative(): void {
        $member = $this->makeMember(HOUSE::REPRESENTATIVES, 'Jane', 'Smith', 'Springfield');
        $url = $member->url(false);
        $this->assertStringNotContainsString('//' . DOMAIN, $url);
        $this->assertStringContainsString('/mp/', $url);
        $this->assertStringContainsString('jane_smith', $url);
    }

    /**
     *
     */
public function test_url_peer_relative(): void {
    $member = $this->makeMember(HOUSE::SENATE, 'John', 'Doe', 'Some Shire');
        $url = $member->url(false);
        $this->assertStringNotContainsString('//' . DOMAIN, $url);
        $this->assertStringContainsString('/peer/', $url);
}

    // =========================================================================

    /**
     * Name formatting in URL slug
     */
    public function test_url_spaces_replaced_with_underscores(): void {
        $member = $this->makeMember(HOUSE::REPRESENTATIVES, 'Mary', 'Van Der Berg', 'Oxford East');
        $url = $member->url(false);
        $this->assertStringContainsString('mary_van_der_berg', $url);
    }

}
