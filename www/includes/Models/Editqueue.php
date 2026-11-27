<?php

namespace OpenAustralia\TWFY\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Editqueue Eloquent model.
 *
 * @property int $edit_id
 * @property int|null $user_id
 * @property string|null $contributor_name
 * @property int|null $edit_type
 * @property int|null $epobject_id_l
 * @property int|null $epobject_id_h
 * @property int|null $glossary_id
 * @property string|null $time_start
 * @property string|null $time_end
 * @property string|null $title
 * @property string|null $body
 * @property string|null $submitted
 * @property int|null $editor_id
 * @property int|null $approved
 * @property string|null $decided
 * @property string|null $reason
 */
class Editqueue extends Model {

    protected $table = 'editqueue';

    protected $primaryKey = 'edit_id';

    protected $fillable = [
        'user_id',
        'contributor_name',
        'edit_type',
        'epobject_id_l',
        'epobject_id_h',
        'glossary_id',
        'time_start',
        'time_end',
        'title',
        'body',
        'submitted',
        'editor_id',
        'approved',
        'decided',
        'reason',
    ];

    /**
     * Copies the submitter's name onto each new submission.
     *
     * The glossary credits this copy and never the account's name when it is shown, so renaming an account, or
     * someone taking one over, can't change who a term credits.
     */
    protected static function booted(): void {
        static::creating(function (Editqueue $edit): void {
            if ($edit->contributor_name === null && $edit->user_id !== null) {
                $edit->contributor_name = User::find($edit->user_id)?->fullName();
            }
        });
    }

    protected $casts = [
        'approved' => 'bool',
    ];

    /**
     * Submissions nobody has decided on, newest first, with the name copied when they were submitted.
     *
     * Each row has edit_id, user_id, contributor_name, glossary_id, title, body and submitted.
     *
     * @param \Illuminate\Database\Eloquent\Builder<Editqueue> $query
     *   The query to narrow.
     *
     * @return \Illuminate\Database\Eloquent\Builder<Editqueue>
     *   The same query, narrowed to the undecided submissions.
     */
    public function scopePending(Builder $query): Builder {
        return $query->whereNull('editqueue.approved')
          ->orderByDesc('editqueue.submitted')
          ->select('editqueue.edit_id', 'editqueue.user_id', 'editqueue.contributor_name', 'editqueue.glossary_id', 'editqueue.title', 'editqueue.body', 'editqueue.submitted');
    }

    /**
     * Makes a pending submission a visible glossary term, and records who approved it.
     *
     * @param int $editor_id
     *   The user who approved it.
     */
    public function approve(int $editor_id): void {
        $this->getConnection()->transaction(function () use ($editor_id) {
            $now = date('Y-m-d H:i:s');
            // The term gets the name copied at submission, never the account's name now, so a later rename or
            // account takeover can't change who a published term credits.
            $term = Glossary::create(['title' => $this->title, 'body' => $this->body, 'contributor_name' => $this->contributor_name, 'type' => 2, 'created' => $now, 'visible' => true]);
            $this->update(['glossary_id' => $term->glossary_id, 'editor_id' => $editor_id, 'approved' => true, 'decided' => $now]);
        });
    }

    /**
     * Turns down a pending submission, and records who did.
     *
     * @param int $editor_id
     *   The user who declined it.
     */
    public function decline(int $editor_id): void {
        $this->update(['editor_id' => $editor_id, 'approved' => false, 'decided' => date('Y-m-d H:i:s')]);
    }

}
