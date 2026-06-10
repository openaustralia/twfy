<?php

use PHPUnit\Framework\TestCase;

if (!defined('DOMAIN')) {
    define('DOMAIN', 'example.com');
}

if (!function_exists('member_full_name')) {
    function member_full_name($house, $title, $first_name, $last_name, $constituency) {
        $name = trim($first_name . ' ' . $last_name);
        if ($title) {
            $name = trim($title . ' ' . $name);
        }
        return $name;
    }
}

if (!function_exists('make_member_url')) {
    function make_member_url($name, $const = '', $house = 1) {
        return 'member-url';
    }
}

if (!class_exists('URL')) {
    class URL {

        private $page;

        public function __construct($page) {
            $this->page = $page;
        }

        public function generate($encode = 'html', $overrideVars = []) {
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
    private function buildMember(int $houseDisp): MEMBER {
        $reflector = new ReflectionClass(MEMBER::class);
        /** @var MEMBER $member */
        $member = $reflector->newInstanceWithoutConstructor();
        $member->house_disp = $houseDisp;
        $member->title = 'Senator';
        $member->first_name = 'Ada';
        $member->last_name = 'Lovelace';
        $member->constituency = 'Tasmania';
        return $member;
    }

    /**
     *
     */
    public function test_url_uses_mp_route_for_representatives_absolute() {
        $member = $this->buildMember(HOUSE::REPRESENTATIVES);

        $this->assertSame('//example.com/mp/member-url', $member->url(true));
    }

    /**
     *
     */
    public function test_url_uses_peer_route_for_senate_relative() {
        $member = $this->buildMember(HOUSE::SENATE);

        $this->assertSame('/peer/member-url', $member->url(false));
    }

    /**
     *
     */
    public function test_url_returns_unknown_for_unexpected_house() {
        $member = $this->buildMember(99);

        $this->assertSame('unknown', $member->url(true));
    }

}
