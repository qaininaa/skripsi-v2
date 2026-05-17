<?php

namespace Domain\Report\Models;

use Domain\User\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-column data point in a section row.
 *
 * Two analyst phases write into this row:
 *   - monitoring → time_start, time_end, sp_value, filled_by_monitoring
 *   - reading    → reading_total, reading_fungi, filled_by_reading
 *
 * @property string      $id
 * @property string      $section_instance_location_id
 * @property int         $column_index
 * @property string|null $sub_column
 * @property string|null $sp_value
 * @property string|null $time_start
 * @property string|null $time_end
 * @property string|null $reading_total
 * @property string|null $reading_fungi
 * @property string|null $location_conclusion   'MS' | 'TMS'
 * @property string|null $filled_by_monitoring
 * @property string|null $filled_by_reading
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
        'sp_value',
        'time_start',
        'time_end',
        'reading_total',
        'reading_fungi',
        'location_conclusion',
        'filled_by_monitoring',
        'filled_by_reading',
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
     * @return BelongsTo<User, SectionEntry>
     */
    public function monitoringFiller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'filled_by_monitoring');
    }

    /**
     * @return BelongsTo<User, SectionEntry>
     */
    public function readingFiller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'filled_by_reading');
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
