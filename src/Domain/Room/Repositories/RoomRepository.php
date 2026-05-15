<?php

namespace Domain\Room\Repositories;

use Shared\Cache\CacheKeyPattern;
use Shared\Cache\CacheTtl;
use Domain\Room\Dtos\CreateRoomDto;
use Domain\Room\Dtos\GetRoomDto;
use Domain\Room\Dtos\GetRoomsFilterDto;
use Domain\Room\Dtos\UpdateRoomDto;
use Domain\Room\Interfaces\RoomRepositoryInterface;
use Domain\Room\Models\Room;
use Illuminate\Support\Facades\Cache;

/**
 * Eloquent implementation of RoomRepositoryInterface with read-through caching.
 *
 * getRooms() results are cached using a key registry pattern to support
 * bulk invalidation when any room is created, updated, or deleted.
 * validation and must always return fresh data.
 */
class RoomRepository implements RoomRepositoryInterface
{
    /**
     * Retrieve a paginated, filtered list of rooms with read-through cache.
     *
     * Generates a unique cache key from the filter parameters and current page.
     * Registers the key in the ROOM_KEYS_REGISTRY so the observer can invalidate it.
     * On cache miss, executes the Eloquent query and stores the result for 24 hours.
     *
     * @param  GetRoomsFilterDto  $data  Filter parameters (search, class).
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getRooms(GetRoomsFilterDto $data)
    {
        $keyHash = md5(json_encode([
            'search' => $data->search,
            'class'  => $data->class,
            'page'   => request()->input('page', 1),
        ]));
        $cacheKey = sprintf(CacheKeyPattern::ROOM_ALL_FILTERED, $keyHash);

        // Register key into the registry (read-modify-write, skip if already present)
        $keys = Cache::get(CacheKeyPattern::ROOM_KEYS_REGISTRY, []);
        if (!in_array($cacheKey, $keys)) {
            $keys[] = $cacheKey;
            Cache::put(CacheKeyPattern::ROOM_KEYS_REGISTRY, $keys, CacheTtl::MASTER);
        }

        return Cache::remember($cacheKey, CacheTtl::MASTER, fn () => Room::query()
            ->when($data->search !== null, function ($query) use ($data) {
                $query->where(function ($subQuery) use ($data) {
                    $subQuery->where('name', 'like', '%' . $data->search . '%')
                        ->orWhere('room_number', 'like', '%' . $data->search . '%');
                });
            })
            ->when($data->class !== null, function ($query) use ($data) {
                $query->where('class', $data->class);
            })
            ->orderBy('class')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString()
        );
    }

    /**
     * Persist a new room to the database.
     *
     * Triggers the 'created' Eloquent event, which causes RoomCacheObserver
     * to invalidate all related cache entries.
     *
     * @param  CreateRoomDto  $data  Data for the new room.
     * @return Room                  The newly created room model.
     */
    public function createRoom(CreateRoomDto $data): Room
    {
        $room = new Room();
        $room->name = $data->name;
        $room->room_number = $data->room_number;
        $room->class = $data->class;
        $room->save();
        return $room;
    }

    /**
     * Find a room by name and room number (case-insensitive).
     *
     * Used for duplicate validation during create and update operations.
     * Intentionally NOT cached - must always return fresh data to prevent false negatives.
     * Optionally excludes a specific room ID to support update uniqueness checks.
     *
     * @param  GetRoomDto  $data  DTO with name, room_number, and optional excludeId.
     * @return Room|null          The matching room, or null if not found.
     */
    public function getRoomByName(GetRoomDto $data): ?Room
    {
        if ($data->name === null && $data->room_number === null) {
            return null;
        }

        $normalizedName       = strtolower(trim($data->name ?? ''));
        $normalizedRoomNumber = strtolower(trim($data->room_number ?? ''));

        return Room::query()
            ->whereRaw('LOWER(name) = ?', [$normalizedName])
            ->whereRaw('LOWER(room_number) = ?', [$normalizedRoomNumber])
            ->when($data->excludeId !== null, fn ($q) => $q->where('id', '!=', $data->excludeId))
            ->first();
    }

    /**
     * Update an existing room with new data.
     *
     * Triggers the 'updated' Eloquent event, which causes RoomCacheObserver
     * to invalidate all related cache entries.
     *
     * @param  Room           $room  The room model to update.
     * @param  UpdateRoomDto  $data  New values to apply.
     * @return void
     */
    public function updateRoom(Room $room, UpdateRoomDto $data): void
    {
        $room->name = $data->name;
        $room->room_number = $data->room_number;
        $room->class = $data->class;
        $room->save();
    }

    /**
     * Delete a room from the database.
     *
     * Triggers the 'deleted' Eloquent event, which causes RoomCacheObserver
     * to invalidate all related cache entries.
     *
     * @param  Room  $room  The room model to delete.
     * @return void
     */
    public function deleteRoom(Room $room): void
    {
        $room->delete();
    }
}
