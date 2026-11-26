<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * A copy of the contributor's name on each submission and glossary term, taken when it was submitted.
 *
 * The glossary shows this copy and never the account's current name, so renaming an account, or someone taking one
 * over, can't change what a published term says.
 */
final class AddContributorNameToGlossary extends AbstractMigration
{
    public function up(): void
    {
        $this->execute('ALTER TABLE editqueue ADD COLUMN contributor_name varchar(255) DEFAULT NULL AFTER user_id');
        $this->execute('ALTER TABLE glossary ADD COLUMN contributor_name varchar(255) DEFAULT NULL AFTER body');

        // Existing rows get the name the account has now, the best record there is.
        $this->execute(
            "UPDATE editqueue JOIN users ON users.user_id = editqueue.user_id
             SET editqueue.contributor_name = TRIM(CONCAT_WS(' ', NULLIF(TRIM(users.firstname), ''), NULLIF(TRIM(users.lastname), '')))"
        );
        // A term takes the name from its earliest approved submission, which is the one the glossary credited.
        $this->execute(
            'UPDATE glossary JOIN editqueue ON editqueue.edit_id = (
                 SELECT eq.edit_id FROM editqueue AS eq
                 WHERE eq.glossary_id = glossary.glossary_id AND eq.approved = 1
                 ORDER BY eq.submitted, eq.edit_id LIMIT 1
             )
             SET glossary.contributor_name = editqueue.contributor_name'
        );
    }

    public function down(): void
    {
        $this->execute('ALTER TABLE glossary DROP COLUMN contributor_name');
        $this->execute('ALTER TABLE editqueue DROP COLUMN contributor_name');
    }
}
