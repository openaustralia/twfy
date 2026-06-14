<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../www/includes/easyparliament/house.php';

class HouseTest extends TestCase {

    public function test_representatives_constant_is_1(): void {
        $this->assertSame(1, HOUSE::REPRESENTATIVES);
    }

    public function test_senate_constant_is_2(): void {
        $this->assertSame(2, HOUSE::SENATE);
    }

    public function test_pretty_name_for_representatives(): void {
        $this->assertSame('Representatives', HOUSE::pretty_name(HOUSE::REPRESENTATIVES));
    }

    public function test_pretty_name_for_senate(): void {
        $this->assertSame('Senators', HOUSE::pretty_name(HOUSE::SENATE));
    }

    public function test_pretty_name_returns_empty_string_for_invalid_house(): void {
        $this->assertSame('', HOUSE::pretty_name(99));
    }

    public function test_pretty_name_uses_default_house_when_primary_invalid(): void {
        $this->assertSame('Senators', HOUSE::pretty_name(99, HOUSE::SENATE));
    }

    public function test_pretty_name_returns_empty_when_both_invalid(): void {
        $this->assertSame('', HOUSE::pretty_name(99, 77));
    }

    public function test_pretty_name_ignores_default_when_primary_valid(): void {
        $this->assertSame('Representatives', HOUSE::pretty_name(HOUSE::REPRESENTATIVES, HOUSE::SENATE));
    }

}
