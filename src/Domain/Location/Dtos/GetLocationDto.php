<?php

namespace Domain\Location\Dtos;

class GetLocationDto
{
    public ?string $room_id;
    public ?string $loc_number;
    public ?string $excludeId;

    public function __construct(array $data)
    {
        $this->room_id     = $data['room_id'] ?? null;
        $this->loc_number  = $data['loc_number'] ?? null;
        $this->excludeId   = $data['exclude_id'] ?? null;
    }
}
