<?php

namespace Domain\Report\Models;

use Domain\Location\Models\Location;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One sampling row inside a SectionInstance, pointing to a Location.
 *
 * @property string $id
 * @property string $section_instance_id
 * @property string $location_id
 * @property int    $display_order
 */
class SectionInstanceLocation extends Model
{
    use HasUuids;

    protected $fillable = [
        'section_instance_id',
        'location_id',
        'display_order',
    ];

    protected $casts = [
        'display_order' => 'integer',
    ];

    /**
     * @return BelongsTo<SectionInstance, SectionInstanceLocation>
     */
    public function instance(): BelongsTo
    {
        return $this->belongsTo(SectionInstance::class, 'section_instance_id');
    }

    /**
     * @return BelongsTo<Location, SectionInstanceLocation>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'location_id');
    }

    /**
     * @return HasMany<SectionEntry>
     */
    public function entries(): HasMany
    {
        return $this->hasMany(SectionEntry::class, 'section_instance_location_id')
            ->orderBy('column_index')
            ->orderBy('sub_column');
    }
}
