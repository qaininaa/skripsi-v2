<?php

namespace Domain\ReportTemplate\Models;

use Domain\Report\Models\Report;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Represents a report template (Jenis Laporan).
 *
 * @property string  $id
 * @property string  $name
 * @property int     $annex_number
 * @property string  $sop_code
 * @property string  $sop_version
 * @property bool    $has_personnel
 * @property Carbon  $created_at
 * @property Carbon  $updated_at
 */
class ReportTemplate extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'annex_number',
        'sop_code',
        'sop_version',
        'has_personnel',
    ];

    protected $casts = [
        'has_personnel' => 'boolean',
        'annex_number'  => 'integer',
    ];

    /**
     * Get all medium templates belonging to this report template.
     *
     * @return HasMany<MediumTemplate>
     */
    public function mediumTemplates(): HasMany
    {
        return $this->hasMany(MediumTemplate::class, 'report_template_id');
    }

    /**
     * Get all incubator templates belonging to this report template.
     *
     * @return HasMany<IncubatorTemplate>
     */
    public function incubatorTemplates(): HasMany
    {
        return $this->hasMany(IncubatorTemplate::class, 'report_template_id');
    }

    /**
     * Get all section templates belonging to this report template, ordered by display order.
     *
     * @return HasMany<Section>
     */
    public function sections(): HasMany
    {
        return $this->hasMany(Section::class, 'report_template_id')
            ->orderBy('order')
            ->orderBy('created_at');
    }

    /**
     * Get all report belonging to this report template.
     *
     * @return HasMany<Report>
     */
    public function Report(): HasMany
    {
        return $this->hasMany(Report::class, 'report_template_id');
    }

    /**
     * Whether this report template includes at least one section
     * with measurement_type = 'swab'.
     */
    public function hasSwab(): bool
    {
        return $this->sections()->where('measurement_type', 'swab')->exists();
    }
}
