<?php

namespace Domain\ReportTemplate\Models;

use Domain\Location\Models\Location;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Represents a section of a report template.
 *
 * @property string  $id
 * @property string  $report_template_id
 * @property string  $measurement_unit
 * @property string  $measurement_type   settle_plate|air_sampler|contact_plate|swab
 * @property int     $max_column
 * @property string|null $column_label
 * @property string  $time_slot_type     by_location|start_end|start_end_ab|start_end_multi
 * @property bool    $has_machine_setup
 * @property int     $order
 * @property Carbon  $created_at
 * @property Carbon  $updated_at
 */
class Section extends Model
{
    use HasUuids;

    protected $fillable = [
        'measurement_unit',
        'measurement_type',
        'max_column',
        'column_label',
        'time_slot_type',
        'has_machine_setup',
    ];

    protected $casts = [
        'has_machine_setup' => 'boolean',
        'max_column'        => 'integer',
    ];

    /**
     * Get the report template this section belongs to.
     *
     * @return BelongsTo<ReportTemplate, Section>
     */
    public function reportTemplate(): BelongsTo
    {
        return $this->belongsTo(ReportTemplate::class, 'report_template_id');
    }

    /**
     * Get all locations assigned to this section,
     * ordered by section_assigned_at (oldest → newest).
     *
     * @return HasMany<Location>
     */
    public function locations(): HasMany
    {
        return $this->hasMany(Location::class, 'section_id')
            ->orderBy('section_assigned_at', 'asc')
            ->orderBy('created_at', 'asc');
    }

    /**
     * Get the human-readable label for the measurement type.
     */
    public function getMeasurementTypeLabel(): string
    {
        return match ($this->measurement_type) {
            'settle_plate'  => 'Settle Plate',
            'air_sampler'   => 'Air Sampler',
            'contact_plate' => 'Contact Plate',
            'swab'          => 'Swab',
            default         => ucwords(str_replace('_', ' ', $this->measurement_type)),
        };
    }

    /**
     * Get the human-readable label for the time slot type.
     */
    public function getTimeSlotLabel(): string
    {
        return match ($this->time_slot_type) {
            'by_location'    => 'Per Lokasi',
            'start_end'      => '1 slot',
            'start_end_ab'   => 'A/B',
            'start_end_multi'=> 'S1/S1-2/S1-3',
            default          => $this->time_slot_type,
        };
    }
}
