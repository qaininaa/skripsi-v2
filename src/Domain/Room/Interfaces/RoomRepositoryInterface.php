<?php

namespace Domain\Room\Interfaces;

use Domain\Room\Dtos\CreateRoomDto;
use Domain\Room\Dtos\GetRoomDto;
use Domain\Room\Dtos\GetRoomsFilterDto;
use Domain\Room\Dtos\UpdateRoomDto;
use Domain\Room\Models\Room;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Contract for Room data access.
 */
interface RoomRepositoryInterface
{
    /**
     * Retrieve a paginated, filtered list of rooms.
     *
     * @param  GetRoomsFilterDto  $data  Filter parameters (search, class).
     * @return LengthAwarePaginator
     */
    public function getRooms(GetRoomsFilterDto $data);

    /**
     * Persist a new room to the database.
     */
    public function createRoom(CreateRoomDto $data): Room;

    /**
     * Find a room by name and room number (case-insensitive).
     *
     * Must NOT be cached — used for duplicate validation during create/update.
     */
    public function getRoomByName(GetRoomDto $data): ?Room;

    /**
     * Find a room by id.
     */
    public function findById(string $id): ?Room;

    /**
     * Determine whether any room location is already used in report snapshots.
     */
    public function hasReportSnapshotUsage(Room $room): bool;

    /**
     * Update an existing room with new data.
     */
    public function updateRoom(Room $room, UpdateRoomDto $data): void;

    /**
     * Delete a room from the database.
     */
    public function deleteRoom(Room $room): void;
}
