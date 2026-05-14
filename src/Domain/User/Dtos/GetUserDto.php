<?php

namespace Domain\User\Dtos;

class GetUserDto
{
    public ?string $username;
    public ?string $password;

    public function __construct(array $data)
    {
        $this->username = $data['username'] ?? null;
        $this->password = $data['password'] ?? null;
    }
}
