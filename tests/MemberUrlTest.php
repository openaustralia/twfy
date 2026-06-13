<?php

/**
 * @file
 * Unit tests for MEMBER::url().
 *
 * These tests set properties directly via reflection and require no database.
 * They rely on make_member_url() and member_full_name() from utility.php.
 */

require_once __DIR__ . '/bootstrap.php';

if (!function_exists('make_member_url')) {
    function make_member_url(string $name, string $const = '', int $house = 1): string {
        $s = [' ', '&amp;', '&ocirc;', '&ouml;', '&acirc;', '&iacute;', '&aacute;', '&uacute;'];
        $r = ['_', 'and', 'o', 'o', 'a', 'i', 'a', 'u'];
        $name = preg_replace('#^the #', '', strtolower($name));
        $out = urlencode(str_replace($s, $r, $name));
        if ($const && ($house == 1 || $house == 2)) {
            $out .= '/' . urlencode(str_replace($s, $r, strtolower($const)));
        } elseif ($house == 0) {
            $out = 'elizabeth_the_second';
        }
        return $out;
    }
}

if (!function_exists('member_full_name')) {
    function member_full_name(int $house, string $title, string $first_name, string $last_name, string $constituency): string {
        if ($house >= 1 && $house <= 4) {
            $s = $first_name . ' ' . $last_name;
            if ($title) {
                $s = $title . ' ' . $s;
            }
            return $s;
        }
        return '';
    }
}

if (!class_exists('URL')) {
    /**
     * Minimal URL stub for MEMBER::url() tests.
     */
    class URL {

        private string $page;

        public function __construct(string $page) {
            $this->page = $page;
        }

        public function insert(array $params): void {
        }

        public function generate(string $type = ''): string {
            return '/' . $this->page . '/';
        }

    }
}

class MemberUrlTest extends \PHPUnit\Framework\TestCase {

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
    // Absolute URLs (default)

    public function test_url_mp_absolute(): void {
        $member = $this->makeMember(1, 'Jane', 'Smith', 'Springfield');
        $url = $member->url(true);
        $this->assertStringStartsWith('//' . DOMAIN, $url);
        $this->assertStringContainsString('/mp/', $url);
        $this->assertStringContainsString('jane_smith', $url);
        $this->assertStringContainsString('springfield', $url);
    }

    public function test_url_peer_absolute(): void {
        $member = $this->makeMember(2, 'John', 'Doe', 'Some Shire');
        $url = $member->url(true);
        $this->assertStringStartsWith('//' . DOMAIN, $url);
        $this->assertStringContainsString('/peer/', $url);
        $this->assertStringContainsString('john_doe', $url);
    }

    public function test_url_mla_absolute(): void {
        $member = $this->makeMember(3, 'Alice', 'Jones', 'East Belfast');
        $url = $member->url(true);
        $this->assertStringStartsWith('//' . DOMAIN, $url);
        $this->assertStringContainsString('/mla/', $url);
    }

    public function test_url_msp_absolute(): void {
        $member = $this->makeMember(4, 'Bob', 'Brown', 'Edinburgh East');
        $url = $member->url(true);
        $this->assertStringStartsWith('//' . DOMAIN, $url);
        $this->assertStringContainsString('/msp/', $url);
    }

    public function test_url_royal_absolute(): void {
        $member = $this->makeMember(0, '', '', '');
        $url = $member->url(true);
        $this->assertStringStartsWith('//' . DOMAIN, $url);
        $this->assertStringContainsString('/royal/', $url);
        // Royal members use 'elizabeth_the_second' slug regardless of name.
        $this->assertStringContainsString('elizabeth_the_second', $url);
    }

    // =========================================================================
    // Relative URLs

    public function test_url_mp_relative(): void {
        $member = $this->makeMember(1, 'Jane', 'Smith', 'Springfield');
        $url = $member->url(false);
        $this->assertStringNotContainsString('//' . DOMAIN, $url);
        $this->assertStringContainsString('/mp/', $url);
        $this->assertStringContainsString('jane_smith', $url);
    }

    public function test_url_peer_relative(): void {
        $member = $this->makeMember(2, 'John', 'Doe', 'Some Shire');
        $url = $member->url(false);
        $this->assertStringNotContainsString('//' . DOMAIN, $url);
        $this->assertStringContainsString('/peer/', $url);
    }

    // =========================================================================
    // Name formatting in URL slug

    public function test_url_spaces_replaced_with_underscores(): void {
        $member = $this->makeMember(1, 'Mary', 'Van Der Berg', 'Oxford East');
        $url = $member->url(false);
        $this->assertStringContainsString('mary_van_der_berg', $url);
    }

}
