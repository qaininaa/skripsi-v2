<?php

namespace Domain\Room\Interfaces;

use Domain\Room\Dtos\CreateRoomDto;
use Domain\Room\Dtos\GetRoomDto;
use Domain\Room\Dtos\GetRoomsFilterDto;
use Domain\Room\Dtos\UpdateRoomDto;
use Domain\Room\Models\Room;

/**
 * Contract for Room data access.
 *
 */
interface RoomRepositoryInterface
{
    /**
     * Retrieve a paginated, filtered list of rooms.
     *
     * @param  GetRoomsFilterDto  $data  Filter parameters (search, class).
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getRooms(GetRoomsFilterDto $data);

    /**
     * Persist a new room to the database.
     *
     * @param  CreateRoomDto  $data  
     * @return Room             
     */
    public function createRoom(CreateRoomDto $data): Room;

    /**
     * Find a room by name and room number (case-insensitive).
     *
     * Must NOT be cached — used for duplicate validation during create/update.
     *
     * @param  GetRoomDto  $data  
     * @return Room|null         
     */
    public function getRoomByName(GetRoomDto $data): ?Room;

    /**
     * Update an existing room with new data.
     *
     * @param  Room           $room  
     * @param  UpdateRoomDto  $data 
     * @return void
     */
    public function updateRoom(Room $room, UpdateRoomDto $data): void;

    /**
     * Delete a room from the database.
     *
     * @param  Room  $room  
     * @return void
     */
    public function deleteRoom(Room $room): void;
}
