<?php

namespace Domain\Room\Services;

use Domain\Room\Dtos\CreateRoomDto;
use Domain\Room\Dtos\GetRoomDto;
use Domain\Room\Dtos\GetRoomsFilterDto;
use Domain\Room\Dtos\UpdateRoomDto;
use Domain\Room\Interfaces\RoomRepositoryInterface;
use Domain\Room\Models\Room;

class RoomService
{
    protected RoomRepositoryInterface $repository;

    public function __construct(RoomRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function getDataRooms(GetRoomsFilterDto $dto)
    {
        try {
            return $this->repository->getRooms($dto);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function createRoom(CreateRoomDto $dto): Room
    {
        try {
            $existingRoom = $this->repository->getRoomByName(new GetRoomDto([
                'name'        => $dto->name,
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

    public function updateRoom(Room $room, UpdateRoomDto $dto): void
    {
        try {
            $existingRoom = $this->repository->getRoomByName(new GetRoomDto([
                'name'        => $dto->name,
                'room_number' => $dto->room_number,
                'exclude_id'  => $room->id,
            ]));

            if ($existingRoom !== null) {
                throw new \RuntimeException('Ruangan dengan nama dan nomor ruangan tersebut sudah ada.');
            }

            $this->repository->updateRoom($room, $dto);
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
