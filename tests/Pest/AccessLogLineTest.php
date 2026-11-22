<?php

/**
 * @file
 * Parsing data.openaustralia.org.au access log lines. Uses documentation IP
 * addresses and made-up requests.
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use OpenAustralia\TWFY\UsageStats\AccessLogLine;

describe('AccessLogLine::parse()', function () {
    describe('with a combined format line', function () {
        beforeEach(function () {
            $this->row = AccessLogLine::parse('203.0.113.5 - - [21/Nov/2026:10:12:13 +1100] '
                . '"GET /scrapedxml/senate_debates/2026-11-20.xml HTTP/1.1" 200 51234 '
                . '"https://example.org/data" "ExampleBot/1.0 (\"quoted\")"');
        });

        it('converts the time to UTC', function () {
            expect($this->row['request_time'])->toBe('2026-11-20 23:12:13');
        });

        it('reads the IP address', function () {
            expect($this->row['ip_address'])->toBe('203.0.113.5');
        });

        it('reads the method', function () {
            expect($this->row['method'])->toBe('GET');
        });

        it('reads the path', function () {
            expect($this->row['path'])->toBe('/scrapedxml/senate_debates/2026-11-20.xml');
        });

        it('reads the status', function () {
            expect($this->row['status'])->toBe(200);
        });

        it('reads the bytes', function () {
            expect($this->row['bytes'])->toBe(51234);
        });

        it('reads the referrer', function () {
            expect($this->row['referrer'])->toBe('https://example.org/data');
        });

        it('unescapes quotes in the user agent', function () {
            expect($this->row['user_agent'])->toBe('ExampleBot/1.0 ("quoted")');
        });
    });

    describe('with dashes for empty fields', function () {
        beforeEach(function () {
            $this->row = AccessLogLine::parse('2001:db8::1 - - [21/Nov/2026:10:12:13 +1100] "HEAD / HTTP/1.1" 304 - "-" "-"');
        });

        it('reads an IPv6 address', function () {
            expect($this->row['ip_address'])->toBe('2001:db8::1');
        });

        it('counts no bytes', function () {
            expect($this->row['bytes'])->toBe(0);
        });

        it('leaves the referrer empty', function () {
            expect($this->row['referrer'])->toBe('');
        });

        it('leaves the user agent empty', function () {
            expect($this->row['user_agent'])->toBe('');
        });
    });

    describe('with a request Apache could not read', function () {
        beforeEach(function () {
            $this->row = AccessLogLine::parse('203.0.113.5 - - [21/Nov/2026:10:12:13 +1100] "\x16\x03\x01" 400 226 "-" "-"');
        });

        it('leaves the method empty', function () {
            expect($this->row['method'])->toBe('');
        });

        it('keeps the request as the path', function () {
            expect($this->row['path'])->toBe('\x16\x03\x01');
        });
    });

    it('rejects a line in another format', function (string $line) {
        expect(AccessLogLine::parse($line))->toBeNull();
    })->with([
        'blank' => [''],
        'common format' => ['203.0.113.5 - - [21/Nov/2026:10:12:13 +1100] "GET / HTTP/1.1" 200 12'],
        'a bad date' => ['203.0.113.5 - - [yesterday] "GET / HTTP/1.1" 200 12 "-" "-"'],
    ]);

    it('drops invalid UTF-8', function () {
        $row = AccessLogLine::parse("203.0.113.5 - - [21/Nov/2026:10:12:13 +1100] \"GET / HTTP/1.1\" 200 12 \"-\" \"Bot\xff\"");

        expect(mb_check_encoding($row['user_agent'], 'UTF-8'))->toBeTrue();
    });
});
