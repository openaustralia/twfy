<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class DropCampaignersTables extends AbstractMigration
{
    public function up(): void
    {
        // Drop dependent table first
        if ($this->hasTable('campaigners_sent_email')) {
            $this->table('campaigners_sent_email')->drop()->save();
        }
        // Then drop main table
        if ($this->hasTable('campaigners')) {
            $this->table('campaigners')->drop()->save();
        }
    }

    public function down(): void
    {
        // Re-creates the table structures as they were in the legacy schema.
        // Note: data dropped by up() is not recoverable.
        $table = $this->table('campaigners', ['id' => 'campaigner_id', 'signed' => false]);
        $table->addColumn('email', 'string', ['limit' => 255, 'default' => ''])
              ->addColumn('postcode', 'string', ['limit' => 255, 'default' => ''])
              ->addColumn('constituency', 'string', ['limit' => 100, 'default' => ''])
              ->addColumn('token', 'string', ['limit' => 255, 'default' => ''])
              ->addColumn('confirmed', 'boolean', ['default' => false])
              ->addColumn('signup_date', 'datetime')
              ->addIndex(['email'])
              ->addIndex(['confirmed'])
              ->addIndex(['constituency'])
              ->create();

        $table = $this->table('campaigners_sent_email', ['id' => false, 'primary_key' => null]);
        $table->addColumn('campaigner_id', 'integer')
              ->addColumn('email_name', 'string', ['limit' => 100])
              ->addIndex(['campaigner_id', 'email_name'], ['unique' => true])
              ->create();
    }
}
