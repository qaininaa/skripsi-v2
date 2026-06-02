<?php

namespace Domain\Report\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-column data point in a section row.
 *
 * Two analyst phases write into this row:
 *   - monitoring → time_start, time_end, column_label_value
 *   - reading    → cfu_bacteri, cfu_fungi, cfu_total
 *
 * @property string      $id
 * @property string      $section_instance_location_id
 * @property int         $column_index
 * @property string|null $sub_column
 * @property string|null $column_label_value
 * @property string|null $time_start
 * @property string|null $time_end
 * @property string|null $cfu_bacteri
 * @property string|null $cfu_fungi
 * @property string|null $cfu_total
 * @property string|null $conclusion   'MS' | 'TMS'
 */
class SectionEntry extends Model
{
    use HasUuids;

    public const CONCLUSION_MS  = 'MS';
    public const CONCLUSION_TMS = 'TMS';

    protected $fillable = [
        'section_instance_location_id',
        'column_index',
        'sub_column',
        'column_label_value',
        'time_start',
        'time_end',
        'cfu_bacteri',
        'cfu_fungi',
        'cfu_total',
        'conclusion',
    ];

    protected $casts = [
        'column_index' => 'integer',
    ];

    /**
     * @return BelongsTo<SectionInstanceLocation, SectionEntry>
     */
    public function instanceLocation(): BelongsTo
    {
        return $this->belongsTo(SectionInstanceLocation::class, 'section_instance_location_id');
    }

    /**
     * Whether monitoring data has been recorded for this column.
     * Reading values may only be entered for columns that have monitoring time.
     */
    public function hasMonitoringTime(): bool
    {
        return ! empty($this->time_start) || ! empty($this->time_end);
    }
}
