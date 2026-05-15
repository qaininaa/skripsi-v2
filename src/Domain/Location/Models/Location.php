<?php

namespace Domain\Location\Models;

use Domain\Room\Models\Room;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a monitoring location (titik sampling) inside a room.
 *
 * @property string   $id                  
 * @property string   $room_id             
 * @property string   $loc_number          
 * @property string   $frequency          
 * @property string   $measurement_type    
 * @property int|null $alert_limit_total   
 * @property int|null $alert_limit_fungi  
 * @property int|null $alert_action_total
 * @property int|null $alert_action_fungi 
 * @property Carbon   $created_at
 * @property Carbon   $updated_at
 */
class Location extends Model
{
    use HasUuids;

    protected $fillable = [
        'room_id',
        'frequency',
        'loc_number',
        'measurement_type',
        'alert_limit_total',
        'alert_limit_fungi',
        'alert_action_total',
        'alert_action_fungi',
    ];

    /**
     * Get the room this location belongs to.
     *
     * @return BelongsTo<Room, Location>
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id');
    }
}
