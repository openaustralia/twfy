<?php

describe('config/database.php', function () {
    $load = fn (): array => require __DIR__ . '/../../config/database.php';

    afterEach(function () {
        $this->savedHost === false ? putenv('DB_HOST') : putenv('DB_HOST=' . $this->savedHost);
    });

    beforeEach(function () {
        $this->savedHost = getenv('DB_HOST');
    });

    it('defaults to MySQL', function () use ($load) {
        expect($load()['default'])->toBe('mysql');
    });

    describe('with a port in DB_HOST', function () use ($load) {
        beforeEach(fn () => putenv('DB_HOST=db.example:3307'));

        it('takes the host before the colon', function () use ($load) {
            expect($load()['connections']['mysql']['host'])->toBe('db.example');
        });

        it('takes the port after the colon', function () use ($load) {
            expect($load()['connections']['mysql']['port'])->toBe('3307');
        });
    });

    describe('without a port in DB_HOST', function () use ($load) {
        beforeEach(fn () => putenv('DB_HOST=db.example'));

        it('uses port 3306', function () use ($load) {
            expect($load()['connections']['mysql']['port'])->toBe('3306');
        });
    });
});
