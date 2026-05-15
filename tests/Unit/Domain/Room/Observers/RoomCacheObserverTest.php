<?php

namespace Tests\Unit\Domain\Room\Observers;

use Shared\Cache\CacheKeyPattern;
use Domain\Room\Models\Room;
use Domain\Room\Observers\RoomCacheObserver;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class RoomCacheObserverTest extends TestCase
{
    private RoomCacheObserver $observer;

    protected function setUp(): void
    {
        parent::setUp();
        // The array driver is configured in phpunit.xml (CACHE_STORE=array).
        // Flush between tests to ensure isolation.
        Cache::flush();
        $this->observer = new RoomCacheObserver();
    }

    private function makeRoom(string $id = 'test-uuid-1234'): Room
    {
        $room = new Room();
        $room->id = $id;
        return $room;
    }

    // -------------------------------------------------------------------------
    // created()
    // -------------------------------------------------------------------------

    public function test_created_forgets_room_by_id_key(): void
    {
        $room = $this->makeRoom('abc-123');

        Cache::put(sprintf(CacheKeyPattern::ROOM_BY_ID, 'abc-123'), 'value', 60);

        $this->observer->created($room);

        $this->assertTrue(Cache::missing(sprintf(CacheKeyPattern::ROOM_BY_ID, 'abc-123')));
    }

    public function test_created_forgets_room_keys_registry(): void
    {
        $room = $this->makeRoom('abc-123');

        Cache::put(CacheKeyPattern::ROOM_KEYS_REGISTRY, ['master:room:list:hash1'], 60);

        $this->observer->created($room);

        $this->assertTrue(Cache::missing(CacheKeyPattern::ROOM_KEYS_REGISTRY));
    }

    public function test_created_forgets_room_all_key(): void
    {
        $room = $this->makeRoom('abc-123');

        Cache::put(CacheKeyPattern::ROOM_ALL, 'all-rooms', 60);

        $this->observer->created($room);

        $this->assertTrue(Cache::missing(CacheKeyPattern::ROOM_ALL));
    }

    public function test_created_forgets_all_registered_list_keys(): void
    {
        $room = $this->makeRoom('abc-123');

        $listKey = 'master:room:list:somehash';
        Cache::put(CacheKeyPattern::ROOM_KEYS_REGISTRY, [$listKey], 60);
        Cache::put($listKey, 'list-data', 60);

        $this->observer->created($room);

        $this->assertTrue(Cache::missing($listKey));
    }

    // -------------------------------------------------------------------------
    // updated()
    // -------------------------------------------------------------------------

    public function test_updated_forgets_room_by_id_key(): void
    {
        $room = $this->makeRoom('def-456');

        Cache::put(sprintf(CacheKeyPattern::ROOM_BY_ID, 'def-456'), 'value', 60);

        $this->observer->updated($room);

        $this->assertTrue(Cache::missing(sprintf(CacheKeyPattern::ROOM_BY_ID, 'def-456')));
    }

    public function test_updated_forgets_room_keys_registry(): void
    {
        $room = $this->makeRoom('def-456');

        Cache::put(CacheKeyPattern::ROOM_KEYS_REGISTRY, ['master:room:list:hash2'], 60);

        $this->observer->updated($room);

        $this->assertTrue(Cache::missing(CacheKeyPattern::ROOM_KEYS_REGISTRY));
    }

    public function test_updated_forgets_room_all_key(): void
    {
        $room = $this->makeRoom('def-456');

        Cache::put(CacheKeyPattern::ROOM_ALL, 'all-rooms', 60);

        $this->observer->updated($room);

        $this->assertTrue(Cache::missing(CacheKeyPattern::ROOM_ALL));
    }

    // -------------------------------------------------------------------------
    // deleted()
    // -------------------------------------------------------------------------

    public function test_deleted_forgets_room_by_id_key(): void
    {
        $room = $this->makeRoom('ghi-789');

        Cache::put(sprintf(CacheKeyPattern::ROOM_BY_ID, 'ghi-789'), 'value', 60);

        $this->observer->deleted($room);

        $this->assertTrue(Cache::missing(sprintf(CacheKeyPattern::ROOM_BY_ID, 'ghi-789')));
    }

    public function test_deleted_forgets_room_keys_registry(): void
    {
        $room = $this->makeRoom('ghi-789');

        Cache::put(CacheKeyPattern::ROOM_KEYS_REGISTRY, ['master:room:list:hash3'], 60);

        $this->observer->deleted($room);

        $this->assertTrue(Cache::missing(CacheKeyPattern::ROOM_KEYS_REGISTRY));
    }

    public function test_deleted_forgets_room_all_key(): void
    {
        $room = $this->makeRoom('ghi-789');

        Cache::put(CacheKeyPattern::ROOM_ALL, 'all-rooms', 60);

        $this->observer->deleted($room);

        $this->assertTrue(Cache::missing(CacheKeyPattern::ROOM_ALL));
    }

    // -------------------------------------------------------------------------
    // Exception safety — write operations must not be blocked
    // -------------------------------------------------------------------------

    public function test_exception_in_invalidate_does_not_propagate(): void
    {
        $room = $this->makeRoom('err-000');

        // Swap the Cache facade with a mock that throws on forget
        Cache::shouldReceive('forget')->andThrow(new \Exception('cache error'));
        Cache::shouldReceive('get')->andReturn([]);

        // The observer must swallow the exception — no throw should escape
        $this->observer->created($room);
        $this->observer->updated($room);
        $this->observer->deleted($room);

        // If we reach here, the exception was properly caught
        $this->assertTrue(true);
    }
}
