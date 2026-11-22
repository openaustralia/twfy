<?php

namespace OpenAustralia\TWFY\UsageStats;

/**
 * The usage stats settings, from the environment or conf/general.
 */
class Settings {

    /**
     * A setting from the environment, else conf/general, else ''.
     *
     * The environment wins, as it does for DB_*, so the Docker stack can point
     * scripts at its own Elasticsearch.
     */
    public static function get(string $name): string {
        $value = getenv($name);
        if ($value !== false && $value !== '') {
            return $value;
        }
        return defined($name) ? (string) constant($name) : '';
    }

    /**
     * IP addresses of OAF's own servers, from DATA_DOWNLOADS_INTERNAL_IPS.
     *
     * @return string[]
     *   The addresses.
     */
    public static function internalAddresses(): array {
        return array_values(array_filter(array_map('trim', explode(',', self::get('DATA_DOWNLOADS_INTERNAL_IPS')))));
    }

}
