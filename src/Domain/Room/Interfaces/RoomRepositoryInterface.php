<?php

namespace Domain\Room\Interfaces;

use Domain\Room\Dtos\CreateRoomDto;
use Domain\Room\Dtos\GetRoomDto;
use Domain\Room\Dtos\GetRoomsFilterDto;
use Domain\Room\Dtos\UpdateRoomDto;
use Domain\Room\Models\Room;

interface RoomRepositoryInterface
{
    public function getRooms(GetRoomsFilterDto $data);
    public function createRoom(CreateRoomDto $data);
    public function getRoomByName(GetRoomDto $data);
    public function updateRoom(Room $room, UpdateRoomDto $data): void;
    public function deleteRoom(Room $room): void;
}
