<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CoverProofReportWithHansardIndex extends AbstractMigration
{
    public function up(): void
    {
        $this->table('hansard')
            ->addIndex(['major', 'hdate', 'proof', 'modified'], ['name' => 'hansard_major_hdate_proof_modified'])
            ->removeIndexByName('majorhdate')
            ->update();
    }

    public function down(): void
    {
        $this->table('hansard')
            ->addIndex(['major', 'hdate'], ['name' => 'majorhdate'])
            ->removeIndexByName('hansard_major_hdate_proof_modified')
            ->update();
    }
}
