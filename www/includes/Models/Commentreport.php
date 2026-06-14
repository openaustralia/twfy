<?php

namespace OpenAustralia\TWFY\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Commentreport Eloquent model.
 *
 * @property int $report_id
 * @property int|null $comment_id
 * @property int|null $user_id
 * @property string|null $body
 * @property string|null $reported
 * @property string|null $resolved
 * @property int|null $resolvedby
 * @property string|null $locked
 * @property int|null $lockedby
 * @property int $upheld
 * @property string|null $firstname
 * @property string|null $lastname
 * @property string|null $email
 */
class Commentreport extends Model {

    protected $table = 'commentreports';

    protected $primaryKey = 'report_id';

    public $timestamps = true;

}
