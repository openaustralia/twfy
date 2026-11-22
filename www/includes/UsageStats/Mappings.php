<?php

namespace OpenAustralia\TWFY\UsageStats;

/**
 * Fixed field types, so Elasticsearch never guesses a type from a month's first document.
 *
 * Text is stored as keyword, as in They Vote for You's and Planning Alerts' API indices.
 */
class Mappings {

    /**
     * @param array<string, mixed> $properties
     *   Fields that aren't text.
     *
     * @return array<string, mixed>
     *   The mappings for a new index.
     */
    public static function with(array $properties): array {
        $keyword = [
            'match_mapping_type' => 'string',
            'mapping' => ['type' => 'keyword', 'ignore_above' => 1024],
        ];
        return [
            'date_detection' => false,
            'dynamic_templates' => [
                ['strings_as_keywords' => $keyword],
            ],
            'properties' => $properties,
        ];
    }

}
