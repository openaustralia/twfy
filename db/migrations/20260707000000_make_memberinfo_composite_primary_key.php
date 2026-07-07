<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class MakeMemberinfoCompositePrimaryKey extends AbstractMigration
{
    public function up(): void
    {
        $this->execute(
            'ALTER TABLE memberinfo
             DROP INDEX memberinfo_member_id_data_key,
             ADD PRIMARY KEY (member_id, data_key)'
        );
    }

    public function down(): void
    {
        $this->execute(
            'ALTER TABLE memberinfo
             DROP PRIMARY KEY,
             ADD UNIQUE KEY memberinfo_member_id_data_key (member_id, data_key)'
        );
    }
}
