<?php

namespace Domain\Room\Observers;

use Shared\Cache\CacheKeyPattern;
use Domain\Room\Models\Room;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Listens to Eloquent model events on Room and invalidates related cache entries.
 *
 * Registered in AppServiceProvider via Room::observe(RoomCacheObserver::class).
 *
 * Cache invalidation is wrapped in a try/catch so that any cache failure
 * (e.g. disk full, store unavailable) never blocks the underlying write operation.
 */
class RoomCacheObserver
{
    /**
     * Handle the Room "created" event.
     *
     * @param  Room  $room  The newly created room.
     * @return void
     */
    public function created(Room $room): void
    {
        $this->invalidate($room);
    }

    /**
     * Handle the Room "updated" event.
     *
     * @param  Room  $room  The updated room.
     * @return void
     */
    public function updated(Room $room): void
    {
        $this->invalidate($room);
    }

    /**
     * Handle the Room "deleted" event.
     *
     * @param  Room  $room  The deleted room.
     * @return void
     */
    public function deleted(Room $room): void
    {
        $this->invalidate($room);
    }

    /**
     * Invalidate all cache entries related to the given room.
     *
     * Invalidation steps:
     * 1. Forget the room-by-ID cache key.
     * 2. Read the ROOM_KEYS_REGISTRY to get all active filtered-list keys.
     * 3. Forget each registered list key individually.
     * 4. Forget the registry key itself.
     * 5. Forget the ROOM_ALL key (unfiltered list).
     *
     * Any exception is caught and logged — cache failures must not propagate
     * and must not block the write operation that triggered this observer.
     *
     * @param  Room  $room  The room whose related cache should be cleared.
     * @return void
     */
    private function invalidate(Room $room): void
    {
        try {
            Cache::forget(sprintf(CacheKeyPattern::ROOM_BY_ID, $room->id));

            $keys = Cache::get(CacheKeyPattern::ROOM_KEYS_REGISTRY, []);
            foreach ($keys as $key) {
                Cache::forget($key);
            }

            Cache::forget(CacheKeyPattern::ROOM_KEYS_REGISTRY);
            Cache::forget(CacheKeyPattern::ROOM_ALL);
        } catch (\Throwable $e) {
            Log::error('RoomCacheObserver: failed to invalidate cache', [
                'room_id' => $room->id,
                'error'   => $e->getMessage(),
            ]);
        }
    }
}
