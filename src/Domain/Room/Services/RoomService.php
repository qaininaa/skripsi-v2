<?php

namespace Domain\Room\Services;

use Domain\Room\Dtos\CreateRoomDto;
use Domain\Room\Dtos\GetRoomsFilterDto;
use Domain\Room\Dtos\UpdateRoomDto;
use Domain\Room\Interfaces\RoomRepositoryInterface;
use Domain\Room\Models\Room;

class RoomService
{
    public $repository;
    public function __construct(RoomRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function getDataRooms(GetRoomsFilterDto $request)
    {
        try {
            $rooms = $this->repository->getRooms($request);
            return $rooms;
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function createRoom(CreateRoomDto $request)
    {
        try {
            $room = $this->repository->createRoom($request);
            return $room;
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function updateRoom(Room $room, UpdateRoomDto $request): void
    {
        try {
            $this->repository->updateRoom($room, $request);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function deleteRoom(Room $room): void
    {
        try {
            $this->repository->deleteRoom($room);
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
