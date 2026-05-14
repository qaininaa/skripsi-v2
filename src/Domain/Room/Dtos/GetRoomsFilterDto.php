<?php

namespace Domain\Room\Dtos;

class GetRoomsFilterDto
{
    public ?string $search;
    public ?string $class;

    public function __construct(array $data = [])
    {
        $this->search = isset($data['search']) && $data['search'] !== '' ? (string) $data['search'] : null;
        $this->class = isset($data['class']) && $data['class'] !== '' ? (string) $data['class'] : null;
    }
}
