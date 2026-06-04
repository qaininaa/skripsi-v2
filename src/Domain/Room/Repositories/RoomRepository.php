<?php

namespace Domain\Room\Repositories;

use Domain\Room\Dtos\CreateRoomDto;
use Domain\Room\Dtos\GetRoomDto;
use Domain\Room\Dtos\GetRoomsFilterDto;
use Domain\Room\Dtos\UpdateRoomDto;
use Domain\Room\Interfaces\RoomRepositoryInterface;
use Domain\Room\Models\Room;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Shared\Cache\CacheKeyPattern;
use Shared\Cache\CacheTtl;

/**
 * Eloquent implementation of RoomRepositoryInterface with read-through caching.
 */
class RoomRepository implements RoomRepositoryInterface
{
    /**
     * Retrieve a paginated, filtered list of rooms with read-through cache.
     *
     * Generates a unique cache key from the filter parameters and current page.
     * Registers the key in the ROOM_KEYS_REGISTRY so the observer can invalidate it.
     *
     * Only raw data (items + total) is cached. The paginator instance is rebuilt
     *
     * @param  GetRoomsFilterDto  $data  Filter parameters (search, class).
     * @return LengthAwarePaginator
     */
    public function getRooms(GetRoomsFilterDto $data)
    {
        $perPage = 10;
        $page = (int) (request()->input('page', 1) ?: 1);

        $keyHash = md5(json_encode([
            'search' => $data->search,
            'class' => $data->class,
            'page' => $page,
            'per' => $perPage,
        ]));
        $cacheKey = sprintf(CacheKeyPattern::ROOM_ALL_FILTERED, $keyHash);

        // Register key into the registry (read-modify-write, skip if already present)
        $keys = Cache::get(CacheKeyPattern::ROOM_KEYS_REGISTRY, []);
        if (! in_array($cacheKey, $keys)) {
            $keys[] = $cacheKey;
            Cache::put(CacheKeyPattern::ROOM_KEYS_REGISTRY, $keys, CacheTtl::MASTER);
        }

        $cached = Cache::remember($cacheKey, CacheTtl::MASTER, function () use ($data, $perPage, $page) {
            $query = Room::query()
                ->when($data->search !== null, function ($query) use ($data) {
                    $query->where(function ($subQuery) use ($data) {
                        $subQuery->where('name', 'like', '%'.$data->search.'%')
                            ->orWhere('room_number', 'like', '%'.$data->search.'%');
                    });
                })
                ->when($data->class !== null, function ($query) use ($data) {
                    $query->where('class', $data->class);
                })
                ->orderBy('class')
                ->orderBy('name');

            $total = (clone $query)->toBase()->getCountForPagination();
            $items = $total
                ? $query->forPage($page, $perPage)->get()
                : Room::query()->whereRaw('0 = 1')->get();

            return [
                'items' => $items,
                'total' => $total,
            ];
        });

        // Rebuild paginator per request so path and query string follow the current URL.
        $paginator = new LengthAwarePaginator(
            $cached['items'],
            $cached['total'],
            $perPage,
            $page,
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => 'page',
            ]
        );

        return $paginator->withQueryString();
    }

    /**
     * Persist a new room to the database.
     *
     * Triggers the 'created' Eloquent event, which causes RoomCacheObserver
     * to invalidate all related cache entries.
     */
    public function createRoom(CreateRoomDto $data): Room
    {
        $room = new Room;
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
     */
    public function getRoomByName(GetRoomDto $data): ?Room
    {
        if ($data->name === null && $data->room_number === null) {
            return null;
        }

        $normalizedName = strtolower(trim($data->name ?? ''));
        $normalizedRoomNumber = strtolower(trim($data->room_number ?? ''));

        return Room::query()
            ->whereRaw('LOWER(name) = ?', [$normalizedName])
            ->whereRaw('LOWER(room_number) = ?', [$normalizedRoomNumber])
            ->when($data->excludeId !== null, fn ($q) => $q->where('id', '!=', $data->excludeId))
            ->first();
    }

    /**
     * {@inheritDoc}
     */
    public function findById(string $id): ?Room
    {
        return Room::find($id);
    }

    /**
     * {@inheritDoc}
     */
    public function hasReportSnapshotUsage(Room $room): bool
    {
        return DB::table('section_instance_locations')
            ->join('locations', 'section_instance_locations.location_id', '=', 'locations.id')
            ->where('locations.room_id', $room->id)
            ->exists();
    }

    /**
     * Update an existing room with new data.
     *
     * Triggers the 'updated' Eloquent event, which causes RoomCacheObserver
     * to invalidate all related cache entries.
     *
     * @param  Room
     * @param  UpdateRoomDto
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
     */
    public function deleteRoom(Room $room): void
    {
        $room->delete();
    }
}
