<?php

/**
 * @file
 * Importing data.openaustralia.org.au access logs into data_downloads. Uses
 * documentation IP addresses and made-up requests.
 */

require_once __DIR__ . '/../bootstrap.php';

use OpenAustralia\TWFY\Models\DataDownload;
use OpenAustralia\TWFY\UsageStats\DownloadImport;

uses(TransactionalTestCase::class);

/**
 * A combined format line for a request at the given Sydney time.
 */
function download_import_spec_line(string $time, string $path = '/scrapedxml/'): string {
    return "203.0.113.5 - - [$time +1100] \"GET $path HTTP/1.1\" 200 512 \"-\" \"ExampleBot/1.0\"\n";
}

/**
 * Writes lines to a temporary log file, gzipped if asked, and returns its path.
 */
function download_import_spec_file(array $lines, bool $gzip = false): string {
    $path = tempnam(sys_get_temp_dir(), 'data_access');
    file_put_contents($path, $gzip ? gzencode(implode('', $lines)) : implode('', $lines));
    return $path;
}

beforeEach(function () {
    $this->files = [];
    $this->import = function (array $lines, bool $gzip = false) {
        $this->files[] = $path = download_import_spec_file($lines, $gzip);
        return DownloadImport::fromFile($path);
    };
});

afterEach(function () {
    array_map('unlink', $this->files);
});

describe('DownloadImport::fromFile()', function () {
    describe('with a day of requests', function () {
        beforeEach(function () {
            $this->counts = ($this->import)([
                download_import_spec_line('21/Nov/2026:00:00:05', '/scrapedxml/a.xml'),
                download_import_spec_line('21/Nov/2026:23:59:59', '/scrapedxml/b.xml'),
                "not a log line\n",
            ]);
        });

        it('imports each request', function () {
            expect(DataDownload::orderBy('id')->pluck('path')->all())->toBe(['/scrapedxml/a.xml', '/scrapedxml/b.xml']);
        });

        it('stores times in UTC', function () {
            expect(DataDownload::min('request_time'))->toBe('2026-11-20 13:00:05');
        });

        it('counts what it did', function () {
            expect($this->counts)->toBe(['imported' => 2, 'already_imported' => 0, 'unreadable' => 1]);
        });

        describe('when imported again', function () {
            beforeEach(function () {
                $this->again = ($this->import)([
                    download_import_spec_line('21/Nov/2026:00:00:05', '/scrapedxml/a.xml'),
                    download_import_spec_line('21/Nov/2026:23:59:59', '/scrapedxml/b.xml'),
                ]);
            });

            it('adds nothing', function () {
                expect(DataDownload::count())->toBe(2);
            });

            it('counts them as already imported', function () {
                expect($this->again['already_imported'])->toBe(2);
            });
        });

        describe('with the next day', function () {
            beforeEach(function () {
                ($this->import)([download_import_spec_line('22/Nov/2026:00:00:01', '/scrapedxml/c.xml')]);
            });

            it('adds the new requests', function () {
                expect(DataDownload::count())->toBe(3);
            });
        });
    });

    it('reads a gzipped log', function () {
        ($this->import)([download_import_spec_line('21/Nov/2026:10:00:00')], true);

        expect(DataDownload::count())->toBe(1);
    });

    it('imports more than one chunk', function () {
        $lines = array_map(fn (int $i) => download_import_spec_line(sprintf('21/Nov/2026:10:%02d:%02d', intdiv($i, 60), $i % 60)), range(0, 1200));
        ($this->import)($lines);

        expect(DataDownload::count())->toBe(1201);
    });

    it('fails on a missing file', function () {
        expect(fn () => DownloadImport::fromFile('/nonexistent/data_access.log'))->toThrow(RuntimeException::class);
    });
});
