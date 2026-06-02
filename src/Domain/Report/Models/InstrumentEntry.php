<?php

namespace Domain\Report\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One instrument record (Air Sampler / Swab Kit / etc.) tied to a single Report.
 *
 * @property string      $id
 * @property string      $report_id
 * @property string      $tool_name        Default 'Air Sampler'
 * @property string|null $no_id
 * @property Carbon|null $calibration_date
 * @property Carbon|null $due_date
 */
class InstrumentEntry extends Model
{
    use HasUuids;

    protected $fillable = [
        'report_id',
        'tool_name',
        'no_id',
        'calibration_date',
        'due_date',
    ];

    protected $casts = [
        'calibration_date' => 'date',
        'due_date'         => 'date',
    ];

    /**
     * @return BelongsTo<Report, InstrumentEntry>
     */
    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class, 'report_id');
    }
}
