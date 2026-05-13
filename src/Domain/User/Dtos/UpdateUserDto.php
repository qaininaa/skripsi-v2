<?php

namespace Domain\User\Dtos;

class UpdateUserDto
{
    public string $name;
    public string $username;
    public string $role;

    public function __construct(array $data)
    {
        $this->name = $data['name'];
        $this->username = $data['username'];
        $this->role = $data['role'];
    }
}
