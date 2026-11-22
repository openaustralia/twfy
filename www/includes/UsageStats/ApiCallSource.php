<?php

namespace OpenAustralia\TWFY\UsageStats;

use OpenAustralia\TWFY\Models\ApiKey;
use OpenAustralia\TWFY\Models\ApiStat;

/**
 * API calls from api_stats, as documents matching They Vote for You's and
 * Planning Alerts' API indices, so Kibana can chart all three together.
 */
class ApiCallSource implements ExportSource {

    /**
     * @var array<string, string[]>
     */
    private array $methods;

    private string $environment;

    /**
     * @param array<string, string[]> $methods
     *   Each API method's name and the parameters it reads.
     * @param string $environment
     *   The stage, such as production, used in index names.
     */
    public function __construct(array $methods, string $environment) {
        $this->methods = $methods;
        $this->environment = $environment;
    }

    /**
     * {@inheritdoc}
     */
    public function cursorName(): string {
        return 'elasticsearch:api_stats';
    }

    /**
     * {@inheritdoc}
     */
    public function indexPrefix(): string {
        return "oa-api-{$this->environment}";
    }

    /**
     * {@inheritdoc}
     */
    public function mappings(): array {
        return Mappings::with([
            'query_time' => ['type' => 'date'],
            'user' => ['properties' => ['id' => ['type' => 'long']]],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function documentsAfter(int $afterId, int $limit): array {
        $rows = ApiStat::where('id', '>', $afterId)->orderBy('id')->limit($limit)->get();
        // MySQL matches keys without regard to case, as api_log_call()'s lookup does, so PHP must too.
        $keyIds = array_change_key_case(
            ApiKey::whereIn('api_key', $rows->pluck('api_key')->unique()->values())->pluck('id', 'api_key')->all()
        );
        return $rows->map(fn (ApiStat $row) => [
            'id' => (int) $row->id,
            'time' => $row->query_time,
            'body' => $this->body($row, $keyIds[strtolower($row->api_key)] ?? null),
        ])->all();
    }

    /**
     * The document for one call. Holds the key's id, never the key itself.
     *
     * @return array<string, mixed>
     *   The document body.
     */
    public function body(ApiStat $row, ?int $keyId): array {
        $query = self::withoutKey(mb_scrub($row->query, 'UTF-8'));
        $parts = parse_url($query);
        $path = is_array($parts) ? ($parts['path'] ?? '') : $query;
        parse_str(is_array($parts) ? ($parts['query'] ?? '') : '', $params);

        $endpoint = $this->endpoint($path, $params);
        $logged = [];
        foreach (array_merge($this->methods[$endpoint] ?? [], ['output']) as $name) {
            if (array_key_exists($name, $params)) {
                $value = $params[$name];
                $logged[$name] = mb_scrub(is_string($value) ? $value : json_encode($value), 'UTF-8');
            }
        }

        return [
            'site' => 'openaustralia',
            'user' => ['id' => $keyId],
            'endpoint' => $endpoint,
            'params' => (object) $logged,
            'query' => $query,
            'ip_address' => $row->ip_address,
            'query_time' => str_replace(' ', 'T', $row->query_time) . 'Z',
        ];
    }

    /**
     * The API method called, named as api_get_methods() names it when known.
     *
     * @param string $path
     *   The query's path, such as /api/getDebates.
     * @param array<string, mixed> $params
     *   The query's parameters.
     */
    private function endpoint(string $path, array $params): string {
        $method = is_string($params['method'] ?? null) ? $params['method'] : basename($path);
        foreach (array_keys($this->methods) as $known) {
            if (strcasecmp($known, $method) === 0) {
                return $known;
            }
        }
        return mb_scrub(substr($method, 0, 64), 'UTF-8');
    }

    /**
     * Removes any key parameter the stored query still holds.
     */
    public static function withoutKey(string $query): string {
        $parts = explode('?', $query, 2);
        if (count($parts) === 1) {
            return $query;
        }
        $pairs = array_filter(
            explode('&', $parts[1]),
            fn (string $pair) => urldecode(explode('=', $pair, 2)[0]) !== 'key'
        );
        return $pairs ? $parts[0] . '?' . implode('&', $pairs) : $parts[0];
    }

}
