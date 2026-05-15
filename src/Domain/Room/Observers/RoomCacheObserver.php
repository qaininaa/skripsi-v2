<?php

namespace Domain\Room\Observers;

use Shared\Cache\CacheKeyPattern;
use Domain\Room\Models\Room;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RoomCacheObserver
{
    public function created(Room $room): void
    {
        $this->invalidate($room);
    }

    public function updated(Room $room): void
    {
        $this->invalidate($room);
    }

    public function deleted(Room $room): void
    {
        $this->invalidate($room);
    }

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
