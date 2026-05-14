<?php

namespace Domain\Room\Dtos;

class CreateRoomDto
{
    public $name;
    public $room_number;
    public $class;

    public function __construct(array $data)
    {
        $this->name = $data['name'];
        $this->room_number = $data['room_number'];
        $this->class = $data['class'];
    }
}
