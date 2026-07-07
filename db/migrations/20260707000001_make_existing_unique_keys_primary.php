<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class MakeExistingUniqueKeysPrimary extends AbstractMigration
{
    public function up(): void
    {
        $this->execute(
            'ALTER TABLE memberinfo
             DROP INDEX memberinfo_member_id_data_key,
             ADD PRIMARY KEY (member_id, data_key)'
        );

        $this->execute(
            'ALTER TABLE consinfo
             DROP INDEX consinfo_constituency_data_key,
             ADD PRIMARY KEY (constituency, data_key)'
        );

        $this->execute(
            'ALTER TABLE personinfo
             DROP INDEX personinfo_person_id_data_key,
             ADD PRIMARY KEY (person_id, data_key)'
        );

        $this->execute(
            'ALTER TABLE gidredirect
             DROP INDEX gid_from,
             ADD PRIMARY KEY (gid_from)'
        );
    }

    public function down(): void
    {
        $this->execute(
            'ALTER TABLE memberinfo
             DROP PRIMARY KEY,
             ADD UNIQUE KEY memberinfo_member_id_data_key (member_id, data_key)'
        );

        $this->execute(
            'ALTER TABLE consinfo
             DROP PRIMARY KEY,
             ADD UNIQUE KEY consinfo_constituency_data_key (constituency, data_key)'
        );

        $this->execute(
            'ALTER TABLE personinfo
             DROP PRIMARY KEY,
             ADD UNIQUE KEY personinfo_person_id_data_key (person_id, data_key)'
        );

        $this->execute(
            'ALTER TABLE gidredirect
             DROP PRIMARY KEY,
             ADD UNIQUE KEY gid_from (gid_from)'
        );
    }
}
