<?php

namespace Domain\Room\Repositories;

use Domain\Room\Dtos\CreateRoomDto;
use Domain\Room\Dtos\GetRoomDto;
use Domain\Room\Dtos\GetRoomsFilterDto;
use Domain\Room\Dtos\UpdateRoomDto;
use Domain\Room\Interfaces\RoomRepositoryInterface;
use Domain\Room\Models\Room;

class RoomRepository implements RoomRepositoryInterface
{

    public function getRooms(GetRoomsFilterDto $data)
    {
        return Room::query()
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
            ->withQueryString();
    }

    public function createRoom(CreateRoomDto $data)
    {
        $room = new Room();
        $room->name = $data->name;
        $room->room_number = $data->room_number;
        $room->class = $data->class;
        $room->save();
        return $room;
    }

    public function getRoomByName(GetRoomDto $data)
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

    public function updateRoom(Room $room, UpdateRoomDto $data): void
    {
        $room->name = $data->name;
        $room->room_number = $data->room_number;
        $room->class = $data->class;
        $room->save();
    }

    public function deleteRoom(Room $room): void
    {
        $room->delete();
    }
}
