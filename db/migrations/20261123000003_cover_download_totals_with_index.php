<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CoverDownloadTotalsWithIndex extends AbstractMigration
{
    public function up(): void
    {
        $this->table('data_downloads')
            ->addIndex(['request_time', 'ip_address'], ['name' => 'data_downloads_request_time_ip_address'])
            ->removeIndexByName('request_time')
            ->update();
    }

    public function down(): void
    {
        $this->table('data_downloads')
            ->addIndex(['request_time'], ['name' => 'request_time'])
            ->removeIndexByName('data_downloads_request_time_ip_address')
            ->update();
    }
}
