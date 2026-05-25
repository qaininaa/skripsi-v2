<?php

namespace Domain\Report\Models;

use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Approval pipeline row.
 *
 * One row per (report, step) where step is 2 (supervisor) or 3 (manager).
 *
 * Lifecycle of a row:
 *   - created with status=pending when the previous step finishes
 *   - status=approved after the assignee signs off
 *   - status=returned after the assignee sends the report back to an analyst
 *     with notes + returned_to_user_id set
 *
 * @property string      $id
 * @property string      $report_id
 * @property int         $step
 * @property string      $role_label              e.g. 'supervisor' | 'manager'
 * @property string|null $user_id                 assignee
 * @property Carbon|null $signed_at
 * @property string      $status                  pending|approved|returned
 * @property string|null $notes
 * @property string|null $returned_to_user_id     analyst id when status=returned
 */
class ReportApproval extends Model
{
    use HasUuids;

    public const STEP_SUPERVISOR = 2;
    public const STEP_MANAGER    = 3;

    public const STATUS_PENDING  = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_RETURNED = 'returned';

    public const ROLE_SUPERVISOR = 'supervisor';
    public const ROLE_MANAGER    = 'manager';

    protected $fillable = [
        'report_id',
        'step',
        'role_label',
        'user_id',
        'signed_at',
        'status',
        'notes',
        'returned_to_user_id',
    ];

    protected $casts = [
        'step'      => 'integer',
        'signed_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Report, ReportApproval>
     */
    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class, 'report_id');
    }

    /**
     * @return BelongsTo<User, ReportApproval>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<User, ReportApproval>
     */
    public function returnedToUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_to_user_id');
    }
}
