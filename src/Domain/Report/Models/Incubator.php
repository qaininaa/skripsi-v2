<?php

namespace Domain\Report\Models;

use Domain\ReportTemplate\Models\IncubatorTemplate;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Per-report incubator instance, derived from an IncubatorTemplate.
 *
 * Holds the No. ID, calibration date, due date for a single incubator unit
 * in the context of one Report. Exactly one IncubatorEntry is created for
 * each medium type that needs to live inside this incubator: 'monitoring'
 * always, plus 'swab' when the report template includes a swab section.
 *
 * @property string      $id
 * @property string      $report_id
 * @property string      $incubator_template_id
 * @property string|null $no_id
 * @property Carbon|null $calibration_date
 * @property Carbon|null $due_date_calibration
 */
class Incubator extends Model
{
    use HasUuids;

    protected $fillable = [
        'report_id',
        'incubator_template_id',
        'no_id',
        'calibration_date',
        'due_date_calibration',
    ];

    protected $casts = [
        'calibration_date'     => 'date',
        'due_date_calibration' => 'date',
    ];

    /**
     * @return BelongsTo<Report, Incubator>
     */
    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class, 'report_id');
    }

    /**
     * @return BelongsTo<IncubatorTemplate, Incubator>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(IncubatorTemplate::class, 'incubator_template_id');
    }

    /**
     * @return HasMany<IncubatorEntry>
     */
    public function entries(): HasMany
    {
        return $this->hasMany(IncubatorEntry::class, 'incubator_id');
    }
}
