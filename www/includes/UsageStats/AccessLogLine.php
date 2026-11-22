<?php

namespace OpenAustralia\TWFY\UsageStats;

/**
 * Parses one line of Apache's "combined" log format into data_downloads columns.
 */
class AccessLogLine {

    private const PATTERN = '/^(\S+) \S+ \S+ \[([^\]]+)\] "((?:[^"\\\\]|\\\\.)*)" (\d{3}) (\d+|-) "((?:[^"\\\\]|\\\\.)*)" "((?:[^"\\\\]|\\\\.)*)"/';

    /**
     * @return array<string, string|int>|null
     *   The columns, or null if the line isn't in combined format.
     */
    public static function parse(string $line): ?array {
        if (!preg_match(self::PATTERN, $line, $m)) {
            return null;
        }
        [, $ip, $time, $request, $status, $bytes, $referrer, $userAgent] = $m;

        $when = \DateTimeImmutable::createFromFormat('d/M/Y:H:i:s O', $time);
        if ($when === false || strlen($ip) > 45) {
            return null;
        }

        // Apache logs a request it couldn't read, such as a TLS probe, as-is.
        if (preg_match('/^(\S{1,16}) (\S+)(?: \S+)?$/', $request, $r)) {
            [, $method, $path] = $r;
        } else {
            $method = '';
            $path = $request;
        }

        return [
            'request_time' => $when->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s'),
            'ip_address' => $ip,
            'method' => self::text($method),
            'path' => self::text($path),
            'status' => (int) $status,
            'bytes' => $bytes === '-' ? 0 : (int) $bytes,
            'referrer' => $referrer === '-' ? '' : self::text($referrer),
            'user_agent' => $userAgent === '-' ? '' : self::text($userAgent),
        ];
    }

    /**
     * Undoes Apache's escaping of quotes and backslashes, and drops invalid UTF-8.
     */
    private static function text(string $value): string {
        return mb_scrub(strtr($value, ['\\"' => '"', '\\\\' => '\\']), 'UTF-8');
    }

}
