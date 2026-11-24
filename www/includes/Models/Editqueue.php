<?php

namespace OpenAustralia\TWFY\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Editqueue Eloquent model.
 *
 * @property int $edit_id
 * @property int|null $user_id
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

    protected $casts = [
        'approved' => 'bool',
    ];

    /**
     * Submissions nobody has decided on, newest first, with who submitted them.
     *
     * Each row has edit_id, user_id, firstname, lastname, glossary_id, title, body and submitted.
     *
     * @param \Illuminate\Database\Eloquent\Builder<Editqueue> $query
     *   The query to narrow.
     *
     * @return \Illuminate\Database\Eloquent\Builder<Editqueue>
     *   The same query, joined to the user.
     */
    public function scopePending(Builder $query): Builder {
        return $query->join('users', 'users.user_id', '=', 'editqueue.user_id')
          ->whereNull('editqueue.approved')
          ->orderByDesc('editqueue.submitted')
          ->select('editqueue.edit_id', 'editqueue.user_id', 'users.firstname', 'users.lastname', 'editqueue.glossary_id', 'editqueue.title', 'editqueue.body', 'editqueue.submitted');
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
            $term = Glossary::create(['title' => $this->title, 'body' => $this->body, 'type' => 2, 'created' => $now, 'visible' => true]);
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
