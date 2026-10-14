<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

/**
 * Adds hansard.proof, which records whether a row came from a proof (draft)
 * Hansard. proof = 1 (true) means the text is NOT final: the Parliament has
 * not yet published the finished version, so wording, speaker attribution and
 * division votes can still change. proof = 0 (false) means the text is final.
 * proof = NULL means we do not know yet.
 *
 * Mirrors the <proof> element in the Parliament's Hansard XML ("1" = proof,
 * "0" = final). Existing rows stay NULL until a backfill reloads them, so the
 * site must not treat NULL as final.
 */
final class AddProofToHansard extends AbstractMigration
{
    public function change(): void
    {
        $this->table('hansard')
            ->addColumn('proof', 'boolean', [
                'null' => true,
                'default' => null,
                'comment' => 'true = proof (draft) Hansard, not the final version; false = final; NULL = unknown',
            ])
            ->update();
    }
}
