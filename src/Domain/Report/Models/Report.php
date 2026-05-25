<?php

namespace Domain\Report\Models;

use Domain\ReportTemplate\Models\ReportTemplate;
use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Represents a reporting task (Tugas Pelaporan) and its monitoring lifecycle.
 *
 * Lifecycle:
 *  - pending       — created by Admin QC, no analyst assigned yet
 *  - in_progress   — an analyst clicked "Mulai" and was locked in via locked_by
 *  - completed     — submitted by analyst (placeholder for next phase)
 *  - archived      — finalized
 *
 * Ownership columns:
 *  - created_by → Admin QC who created this task
 *  - locked_by  → analyst currently locked into the monitoring (mirror of analysts.type=monitoring)
 *
 * @property string      $id
 * @property string      $report_template_id
 * @property string|null $created_by             user_id of admin QC who created the task
 * @property string|null $locked_by              user_id of analyst who started monitoring
 * @property string      $product_name
 * @property string      $batch_number
 * @property string      $status                 pending|in_progress|completed|archived
 * @property Carbon|null $monitoring_started_at
 * @property Carbon|null $printed_at
 * @property string|null $printed_by
 * @property Carbon      $created_at
 * @property Carbon      $updated_at
 */
class Report extends Model
{
    use HasUuids;

    public const STATUS_PENDING                = 'pending';
    public const STATUS_IN_PROGRESS_MONITORING = 'in_progress_monitoring';
    public const STATUS_IN_PROGRESS_READING    = 'in_progress_reading';
    public const STATUS_PENDING_REVIEW         = 'pending_review';
    public const STATUS_PENDING_APPROVAL       = 'pending_approval';
    public const STATUS_COMPLETED              = 'completed';
    public const STATUS_ARCHIVED               = 'archived';

    /**
     * Backwards-compat alias: the old generic in_progress status was split
     * into two finer-grained states. Code that still references the old
     * constant should be updated to one of the specific statuses.
     *
     * @deprecated Use STATUS_IN_PROGRESS_MONITORING / STATUS_IN_PROGRESS_READING.
     */
    public const STATUS_IN_PROGRESS = self::STATUS_IN_PROGRESS_MONITORING;

    protected $fillable = [
        'report_template_id',
        'product_name',
        'batch_number',
        'status',
        'created_by',
        'locked_by',
        'monitoring_started_at',
        'printed_at',
        'printed_by',
    ];

    protected $casts = [
        'monitoring_started_at' => 'datetime',
        'printed_at'            => 'datetime',
    ];

    /**
     * @return BelongsTo<ReportTemplate, Report>
     */
    public function reportTemplate(): BelongsTo
    {
        return $this->belongsTo(ReportTemplate::class, 'report_template_id');
    }

    /**
     * Admin QC who originally created this report task.
     *
     * @return BelongsTo<User, Report>
     */
    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Analyst currently locked to this report. Identifies the monitoring owner.
     *
     * @return BelongsTo<User, Report>
     */
    public function lockedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    /**
     * @return BelongsTo<User, Report>
     */
    public function printedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'printed_by');
    }

    /**
     * Pivot rows: analysts attached to this report by role (monitoring|reading).
     *
     * @return HasMany<Analyst>
     */
    public function analysts(): HasMany
    {
        return $this->hasMany(Analyst::class, 'report_id');
    }

    /**
     * @return HasMany<InstrumentEntry>
     */
    public function instrumentEntries(): HasMany
    {
        return $this->hasMany(InstrumentEntry::class, 'report_id');
    }

    /**
     * @return HasMany<MediumEntry>
     */
    public function mediumEntries(): HasMany
    {
        return $this->hasMany(MediumEntry::class, 'report_id');
    }

    /**
     * @return HasMany<Incubator>
     */
    public function incubators(): HasMany
    {
        return $this->hasMany(Incubator::class, 'report_id');
    }

    /**
     * Section blocks of this report (template sections, plus any duplicates
     * Admin QC has produced).
     *
     * @return HasMany<SectionInstance>
     */
    public function sectionInstances(): HasMany
    {
        return $this->hasMany(SectionInstance::class, 'report_id');
    }

    /**
     * Supervisor / manager approval rows attached to this report.
     *
     * @return HasMany<ReportApproval>
     */
    public function approvals(): HasMany
    {
        return $this->hasMany(ReportApproval::class, 'report_id')->orderBy('step');
    }

    /**
     * Get the analyst pivot of the given role, if any.
     */
    public function analystOfType(string $type): ?Analyst
    {
        return $this->analysts->firstWhere('type', $type);
    }

    /**
     * Whether the report is currently in the monitoring phase.
     */
    public function isMonitoringPhase(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS_MONITORING;
    }

    /**
     * Whether the report is currently in the reading phase.
     */
    public function isReadingPhase(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS_READING;
    }
}
