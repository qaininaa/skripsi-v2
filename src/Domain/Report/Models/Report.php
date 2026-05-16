<?php

namespace Domain\Report\Models;

use Domain\ReportTemplate\Models\ReportTemplate;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Represents a reporting task (Tugas Pelaporan).
 *
 * @property string      $id
 * @property string      $report_template_id
 * @property string|null $locked_by
 * @property string      $product_name
 * @property string      $batch_number
 * @property string      $status             pending|in_progress|completed|archived
 * @property Carbon|null $printed_at
 * @property string|null $printed_by
 * @property Carbon      $created_at
 * @property Carbon      $updated_at
 */
class Report extends Model
{
    use HasUuids;

    protected $fillable = [
        'report_template_id',
        'product_name',
        'batch_number',
        'status',
        'locked_by',
        'printed_at',
        'printed_by',
    ];

    protected $casts = [
        'printed_at' => 'datetime',
    ];

    /**
     * Get the report template this report is based on.
     *
     * @return BelongsTo<ReportTemplate, Report>
     */
    public function reportTemplate(): BelongsTo
    {
        return $this->belongsTo(ReportTemplate::class, 'report_template_id');
    }

    /**
     * Get the user who locked this report.
     *
     * @return BelongsTo<User, Report>
     */
    public function lockedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    /**
     * Get the user who printed this report.
     *
     * @return BelongsTo<User, Report>
     */
    public function printedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'printed_by');
    }

    /**
     * Get the user who created this report (using created_by if present, otherwise locked_by).
     *
     * @return BelongsTo<User, Report>
     */
    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }
}
