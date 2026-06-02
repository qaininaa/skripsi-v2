<?php

namespace Domain\Report\Models;

use Domain\ReportTemplate\Models\Section;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

/**
 * One "section block" inside a Report.
 *
 * The first instance of a section gets instance_number = 1 ("asli"). When Admin
 * QC duplicates a section block, a new row with instance_number 2, 3, … is
 * created and parent_instance_id points back to the source.
 *
 * @property string      $id
 * @property string      $report_id
 * @property string      $section_id
 * @property int         $instance_number
 * @property string|null $parent_instance_id
 * @property string|null $duplication_reason
 * @property string|null $note
 * @property string|null $final_conclusion   'MS' | 'TMS' | null
 */
class SectionInstance extends Model
{
    use HasUuids;

    public const CONCLUSION_MS  = 'MS';
    public const CONCLUSION_TMS = 'TMS';

    protected $fillable = [
        'report_id',
        'section_id',
        'instance_number',
        'parent_instance_id',
        'duplication_reason',
        'note',
        'final_conclusion',
    ];

    protected $casts = [
        'instance_number' => 'integer',
    ];

    /**
     * @return BelongsTo<Report, SectionInstance>
     */
    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class, 'report_id');
    }

    /**
     * @return BelongsTo<Section, SectionInstance>
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    /**
     * @return BelongsTo<SectionInstance, SectionInstance>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_instance_id');
    }

    /**
     * @return HasMany<SectionInstance>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_instance_id');
    }

    /**
     * @return HasMany<SectionInstanceLocation>
     */
    public function instanceLocations(): HasMany
    {
        return $this->hasMany(SectionInstanceLocation::class, 'section_instance_id')
            ->orderBy('display_order')
            ->orderBy('created_at');
    }

    /**
     * Convenience: all entries below this instance, regardless of row.
     *
     * @return HasManyThrough<SectionEntry>
     */
    public function entries(): HasManyThrough
    {
        return $this->hasManyThrough(
            SectionEntry::class,
            SectionInstanceLocation::class,
            'section_instance_id',         // FK on locations → instance
            'section_instance_location_id', // FK on entries  → location
            'id',
            'id',
        );
    }

    /**
     * @return HasMany<SectionSignature>
     */
    public function signatures(): HasMany
    {
        return $this->hasMany(SectionSignature::class, 'section_instance_id');
    }

    /**
     * Display label, e.g. "5" for the original or "5.2" for a duplicate.
     */
    public function displayLabel(): string
    {
        $base = (string) ($this->section?->order ?? '?');
        return $this->instance_number > 1
            ? $base . '.' . $this->instance_number
            : $base;
    }
}
