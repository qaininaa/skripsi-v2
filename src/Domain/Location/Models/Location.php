<?php

namespace Domain\Location\Models;

use Domain\Room\Models\Room;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

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

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id');
    }
}