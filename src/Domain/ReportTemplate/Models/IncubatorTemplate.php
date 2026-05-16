<?php

namespace Domain\ReportTemplate\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Represents an incubator template entry inside a report template.
 *
 * @property string  $id
 * @property string  $report_template_id
 * @property string  $label              
 * @property int     $min_day          
 * @property Carbon  $created_at
 * @property Carbon  $updated_at
 */
class IncubatorTemplate extends Model
{
    use HasUuids;

    protected $fillable = [
        'report_template_id',
        'label',
        'min_day',
    ];

    protected $casts = [
        'min_day' => 'integer',
    ];

    /**
     * Get the report template this incubator template belongs to.
     *
     * @return BelongsTo<ReportTemplate, IncubatorTemplate>
     */
    public function reportTemplate(): BelongsTo
    {
        return $this->belongsTo(ReportTemplate::class, 'report_template_id');
    }
}
