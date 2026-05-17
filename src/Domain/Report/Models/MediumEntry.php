<?php

namespace Domain\Report\Models;

use Domain\ReportTemplate\Models\MediumTemplate;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Per-report medium entry. Carries batch number, GPT number, and expiration date
 * for one medium type (TSP 65mm, TSP 90mm, ...) within a single Report.
 *
 * Swab Kit entries are also stored here with is_swab=true and medium_id=null;
 * those entries don't have a GPT number and are always rendered last in the UI.
 *
 * @property string      $id
 * @property string      $report_id
 * @property string|null $medium_id
 * @property string|null $name
 * @property bool        $is_swab
 * @property string|null $batch_number
 * @property string|null $gpt_number
 * @property Carbon|null $expiration_date
 */
class MediumEntry extends Model
{
    use HasUuids;

    protected $fillable = [
        'report_id',
        'medium_id',
        'name',
        'is_swab',
        'batch_number',
        'gpt_number',
        'expiration_date',
    ];

    protected $casts = [
        'is_swab'         => 'boolean',
        'expiration_date' => 'date',
    ];

    /**
     * @return BelongsTo<Report, MediumEntry>
     */
    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class, 'report_id');
    }

    /**
     * @return BelongsTo<MediumTemplate, MediumEntry>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(MediumTemplate::class, 'medium_id');
    }
}
