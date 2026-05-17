<?php

namespace Domain\Report\Models;

use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One incubation cycle inside a single Incubator.
 *
 * medium_type is either 'monitoring' (TSP plates) or 'swab' (Swab Kit medium).
 * If the parent report template doesn't include a swab section, no swab row exists.
 *
 * @property string      $id
 * @property string      $incubator_id
 * @property string      $medium_type
 * @property string|null $incubated_by
 * @property Carbon|null $date_in
 * @property string|null $time_in
 * @property string|null $removed_by
 * @property Carbon|null $date_out
 * @property string|null $time_out
 */
class IncubatorEntry extends Model
{
    use HasUuids;

    protected $fillable = [
        'incubator_id',
        'medium_type',
        'incubated_by',
        'date_in',
        'time_in',
        'removed_by',
        'date_out',
        'time_out',
    ];

    protected $casts = [
        'date_in'  => 'date',
        'date_out' => 'date',
    ];

    /**
     * @return BelongsTo<Incubator, IncubatorEntry>
     */
    public function incubator(): BelongsTo
    {
        return $this->belongsTo(Incubator::class, 'incubator_id');
    }

    /**
     * @return BelongsTo<User, IncubatorEntry>
     */
    public function incubatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'incubated_by');
    }

    /**
     * @return BelongsTo<User, IncubatorEntry>
     */
    public function removedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'removed_by');
    }
}
