<?php

namespace Domain\Room\Models;

use Domain\Location\Models\Location;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents a physical room (ruangan) in the facility.
 *
 * Room is a master data entity. Its list queries are cached via RoomRepository
 * and invalidated automatically by RoomCacheObserver on any write event.
 *
 * @property string $id           UUID primary key.
 * @property string $name         Room display name.
 * @property string $room_number  Room identifier number.
 * @property string $class        Room classification (e.g. 'A', 'B', 'C').
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Room extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'room_number',
        'class',
    ];

    /**
     * Get all monitoring locations inside this room.
     *
     * @return HasMany<Location>
     */
    public function locations(): HasMany
    {
        return $this->hasMany(Location::class, 'room_id');
    }
}
