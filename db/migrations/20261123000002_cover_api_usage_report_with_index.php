<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CoverApiUsageReportWithIndex extends AbstractMigration
{
    public function up(): void
    {
        $this->table('api_daily_usage')
            ->addIndex(['date', 'api_key_id', 'count'], ['name' => 'api_daily_usage_date_api_key_id_count'])
            ->removeIndexByName('date')
            ->update();
    }

    public function down(): void
    {
        $this->table('api_daily_usage')
            ->addIndex(['date'], ['name' => 'date'])
            ->removeIndexByName('api_daily_usage_date_api_key_id_count')
            ->update();
    }
}
