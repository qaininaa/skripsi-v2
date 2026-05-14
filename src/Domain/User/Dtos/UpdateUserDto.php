<?php

namespace Domain\User\Dtos;

class UpdateUserDto
{
    public string $name;
    public string $username;
    public string $role;
    public ?string $password;

    public function __construct(array $data)
    {
        $this->name = $data['name'];
        $this->username = $data['username'];
        $this->role = $data['role'];
        $password = $data['password'] ?? null;
        $this->password = is_string($password) && trim($password) !== '' ? $password : null;
    }

    public function hasPasswordReset(): bool
    {
        return $this->password !== null;
    }
}
