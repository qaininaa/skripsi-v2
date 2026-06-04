<?php

namespace Domain\Room\Services;

use Domain\Room\Dtos\CreateRoomDto;
use Domain\Room\Dtos\GetRoomDto;
use Domain\Room\Dtos\GetRoomsFilterDto;
use Domain\Room\Dtos\UpdateRoomDto;
use Domain\Room\Interfaces\RoomRepositoryInterface;
use Domain\Room\Models\Room;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Handles business logic for the Room domain.
 *
 * Enforces uniqueness rules (name + room_number combination must be unique)
 * and delegates all data access to the RoomRepositoryInterface.
 */
class RoomService
{
    protected RoomRepositoryInterface $repository;

    public function __construct(RoomRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Retrieve a paginated, filtered list of rooms.
     *
     * Results may be served from cache on repeated calls with the same filter.
     *
     * @param  GetRoomsFilterDto  $dto  Filter parameters (search, class).
     * @return LengthAwarePaginator
     */
    public function getDataRooms(GetRoomsFilterDto $dto)
    {
        try {
            return $this->repository->getRooms($dto);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Create a new room, or return the existing one if the combination already exists.
     *
     * Checks for an existing room with the same name and room_number (case-insensitive).
     * If found, returns it without creating a duplicate.
     *
     * @param  CreateRoomDto  $dto  Data for the new room.
     * @return Room The newly created or existing room.
     */
    public function createRoom(CreateRoomDto $dto): Room
    {
        try {
            $existingRoom = $this->repository->getRoomByName(new GetRoomDto([
                'name' => $dto->name,
                'room_number' => $dto->room_number,
            ]));

            if ($existingRoom !== null) {
                return $existingRoom;
            }

            return $this->repository->createRoom($dto);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Update an existing room.
     *
     * Validates that the new name + room_number combination is not already
     * taken by a different room. Throws RuntimeException if a conflict is found.
     *
     * @param  Room  $room  The room model to update.
     * @param  UpdateRoomDto  $dto  New data for the room.
     *
     * @throws \RuntimeException If another room with the same name and number already exists.
     */
    public function updateRoom(Room $room, UpdateRoomDto $dto): void
    {
        try {
            $existingRoom = $this->repository->getRoomByName(new GetRoomDto([
                'name' => $dto->name,
                'room_number' => $dto->room_number,
                'exclude_id' => $room->id,
            ]));

            if ($existingRoom !== null) {
                throw new \RuntimeException('Ruangan dengan nama dan nomor ruangan tersebut sudah ada.');
            }

            $this->repository->updateRoom($room, $dto);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Find room by ID.
     *
     * @throws \RuntimeException
     */
    public function findRoomById(string $roomId): Room
    {
        $room = $this->repository->findById($roomId);
        if ($room === null) {
            throw (new ModelNotFoundException)->setModel(Room::class, [$roomId]);
        }

        return $room;
    }

    public function updateRoomById(string $roomId, UpdateRoomDto $dto): void
    {
        $this->updateRoom($this->findRoomById($roomId), $dto);
    }

    /**
     * Delete a room.
     *
     * Triggers the RoomCacheObserver via Eloquent model events to invalidate related cache.
     *
     * @param  Room  $room  The room model to delete.
     */
    public function deleteRoom(Room $room): void
    {
        try {
            if ($this->repository->hasReportSnapshotUsage($room)) {
                throw new \RuntimeException(
                    'Ruangan tidak dapat dihapus karena titik samplingnya sudah digunakan pada laporan.'
                );
            }

            $this->repository->deleteRoom($room);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function deleteRoomById(string $roomId): void
    {
        $this->deleteRoom($this->findRoomById($roomId));
    }
}
