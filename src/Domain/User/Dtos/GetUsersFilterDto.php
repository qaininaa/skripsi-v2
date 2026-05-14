<?php

namespace Domain\User\Dtos;

class GetUsersFilterDto
{
    public ?string $search;
    public ?string $role;

    public function __construct(array $data = [])
    {
        $this->search = isset($data['search']) && $data['search'] !== '' ? (string) $data['search'] : null;
        $this->role = isset($data['role']) && $data['role'] !== '' ? (string) $data['role'] : null;
    }
}
