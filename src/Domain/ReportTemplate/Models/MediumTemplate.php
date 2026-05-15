<?php

namespace Domain\ReportTemplate\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Represents a medium template entry inside a report template.
 *
 * @property string  $id
 * @property string  $report_template_id
 * @property string  $name
 * @property Carbon  $created_at
 * @property Carbon  $updated_at
 */
class MediumTemplate extends Model
{
    use HasUuids;

    protected $fillable = [
        'report_template_id',
        'name',
    ];

    /**
     * Get the report template this medium template belongs to.
     *
     * @return BelongsTo<ReportTemplate, MediumTemplate>
     */
    public function reportTemplate(): BelongsTo
    {
        return $this->belongsTo(ReportTemplate::class, 'report_template_id');
    }
}
