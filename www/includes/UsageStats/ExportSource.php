<?php

namespace OpenAustralia\TWFY\UsageStats;

/**
 * A table whose rows the Exporter sends to Elasticsearch, oldest id first.
 */
interface ExportSource {

    /**
     * The export_cursors name holding the last id sent.
     */
    public function cursorName(): string;

    /**
     * Index names start with this, then the year, or year and month.
     */
    public function indexPrefix(): string;

    /**
     * Field types for each new index.
     *
     * @return array<string, mixed>
     *   Mappings, as Mappings::with() builds them.
     */
    public function mappings(): array;

    /**
     * Up to $limit rows with an id above $afterId, as documents, oldest first.
     *
     * @return array<int, array<string, mixed>>
     *   Each with its row id ('id'), UTC time as 'Y-m-d H:i:s' ('time') and
     *   document ('body').
     */
    public function documentsAfter(int $afterId, int $limit): array;

}
