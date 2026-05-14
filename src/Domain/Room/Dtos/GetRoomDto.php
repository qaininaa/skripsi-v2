<?php

namespace Domain\Room\Dtos;

class GetRoomDto
{
    public ?string $name;
    public ?string $room_number;
    public ?string $class;

    public function __construct(array $data)
    {
        $this->name = $data['name'] ?? null;
        $this->room_number = $data['room_number'] ?? null;
        $this->class = $data['class'] ?? null;
    }
}
