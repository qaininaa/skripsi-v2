<?php

namespace Domain\Location\Dtos;

class GetLocationsFilterDto
{
    public ?string $search;

    public function __construct(array $data = [])
    {
        $this->search = isset($data['search']) && $data['search'] !== '' ? (string) $data['search'] : null;
    }
}
