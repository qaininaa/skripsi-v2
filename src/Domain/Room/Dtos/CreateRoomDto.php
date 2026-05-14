<?php

namespace Domain\Room\Dtos;

class CreateRoomDto
{
    public string $name;
    public string $room_number;
    public string $class;

    public function __construct(array $data)
    {
        $this->name = $data['name'];
        $this->room_number = $data['room_number'];
        $this->class = $data['class'];
    }
}
