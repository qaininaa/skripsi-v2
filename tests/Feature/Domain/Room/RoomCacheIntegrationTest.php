<?php

namespace Tests\Feature\Domain\Room;

use Shared\Cache\CacheKeyPattern;
use Domain\Room\Dtos\GetRoomsFilterDto;
use Domain\Room\Models\Room;
use Domain\Room\Repositories\RoomRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class RoomCacheIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private RoomRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        // The array driver is configured in phpunit.xml (CACHE_STORE=array).
        // Flush between tests to ensure isolation.
        Cache::flush();
        $this->repository = new RoomRepository();
    }

    private function createRoom(array $attributes = []): Room
    {
        return Room::create(array_merge([
            'name'        => 'Test Room',
            'room_number' => 'TR-01',
            'class'       => 'A',
        ], $attributes));
    }

    private function defaultFilter(): GetRoomsFilterDto
    {
        return new GetRoomsFilterDto([]);
    }

    private function expectedCacheKey(GetRoomsFilterDto $dto, int $page = 1): string
    {
        $keyHash = md5(json_encode([
            'search' => $dto->search,
            'class'  => $dto->class,
            'page'   => $page,
        ]));
        return sprintf(CacheKeyPattern::ROOM_ALL_FILTERED, $keyHash);
    }

    // -------------------------------------------------------------------------
    // Cache miss → DB query → cache populated
    // -------------------------------------------------------------------------

    public function test_cache_is_populated_after_first_call_to_get_rooms(): void
    {
        $this->createRoom();

        $dto      = $this->defaultFilter();
        $cacheKey = $this->expectedCacheKey($dto);

        // Before first call, cache should be empty
        $this->assertFalse(Cache::has($cacheKey));

        // First call — cache miss, hits DB and stores result
        $this->repository->getRooms($dto);

        // After first call, cache should be populated
        $this->assertTrue(Cache::has($cacheKey));
    }

    public function test_second_call_to_get_rooms_returns_cached_result(): void
    {
        $this->createRoom();

        $dto = $this->defaultFilter();

        $first  = $this->repository->getRooms($dto);
        $second = $this->repository->getRooms($dto);

        // Both calls should return the same total count
        $this->assertSame($first->total(), $second->total());
    }

    // -------------------------------------------------------------------------
    // Cache invalidation on update
    // -------------------------------------------------------------------------

    public function test_cache_is_cleared_after_room_update(): void
    {
        $room = $this->createRoom();

        $dto      = $this->defaultFilter();
        $cacheKey = $this->expectedCacheKey($dto);

        // Populate cache
        $this->repository->getRooms($dto);
        $this->assertTrue(Cache::has($cacheKey));
        $this->assertTrue(Cache::has(CacheKeyPattern::ROOM_KEYS_REGISTRY));

        // Update the room — observer fires and clears cache
        $room->name = 'Updated Room Name';
        $room->save();

        $this->assertTrue(Cache::missing($cacheKey));
        $this->assertTrue(Cache::missing(CacheKeyPattern::ROOM_KEYS_REGISTRY));
    }

    // -------------------------------------------------------------------------
    // Cache invalidation on delete
    // -------------------------------------------------------------------------

    public function test_cache_is_cleared_after_room_delete(): void
    {
        $room = $this->createRoom();

        $dto      = $this->defaultFilter();
        $cacheKey = $this->expectedCacheKey($dto);

        // Populate cache
        $this->repository->getRooms($dto);
        $this->assertTrue(Cache::has($cacheKey));
        $this->assertTrue(Cache::has(CacheKeyPattern::ROOM_KEYS_REGISTRY));

        // Delete the room — observer fires and clears cache
        $room->delete();

        $this->assertTrue(Cache::missing($cacheKey));
        $this->assertTrue(Cache::missing(CacheKeyPattern::ROOM_KEYS_REGISTRY));
    }

    // -------------------------------------------------------------------------
    // Cache invalidation on create
    // -------------------------------------------------------------------------

    public function test_cache_is_cleared_after_room_create(): void
    {
        // Seed one room so getRooms() has something to cache
        $this->createRoom(['name' => 'Existing Room', 'room_number' => 'ER-01']);

        $dto      = $this->defaultFilter();
        $cacheKey = $this->expectedCacheKey($dto);

        // Populate cache
        $this->repository->getRooms($dto);
        $this->assertTrue(Cache::has($cacheKey));
        $this->assertTrue(Cache::has(CacheKeyPattern::ROOM_KEYS_REGISTRY));

        // Create a new room — observer fires and clears cache
        Room::create([
            'name'        => 'New Room',
            'room_number' => 'NR-01',
            'class'       => 'B',
        ]);

        $this->assertTrue(Cache::missing($cacheKey));
        $this->assertTrue(Cache::missing(CacheKeyPattern::ROOM_KEYS_REGISTRY));
    }
}
