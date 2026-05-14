<?php

namespace Domain\Location\Dtos;

class GetLocationsFilterDto
{
    public ?string $search;
    public ?string $room_id;

    public function __construct(array $data = [])
    {
        $this->search  = isset($data['search']) && $data['search'] !== '' ? (string) $data['search'] : null;
        $this->room_id = isset($data['room_id']) && $data['room_id'] !== '' ? (string) $data['room_id'] : null;
    }
}
