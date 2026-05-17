<?php

namespace Domain\Report\Models;

use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pivot row linking a User (analyst) to a Report by responsibility type.
 *
 * type:
 *   - monitoring → analyst who runs the monitoring section
 *   - reading    → analyst who reads the cultured plates after incubation
 *
 * @property string $id
 * @property string $user_id
 * @property string $report_id
 * @property string $type
 */
class Analyst extends Model
{
    use HasUuids;

    public const TYPE_MONITORING = 'monitoring';
    public const TYPE_READING    = 'reading';

    protected $fillable = [
        'user_id',
        'report_id',
        'type',
    ];

    /**
     * @return BelongsTo<User, Analyst>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<Report, Analyst>
     */
    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class, 'report_id');
    }
}
