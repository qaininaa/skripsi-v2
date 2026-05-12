<?php

namespace App\Domain\User\Dtos;

class CreateUserDto
{
    public $name;
    public $username;
    public $password;
    public $role;

    public function __construct(array $data)
    {
        $this->name = $data['name'];
        $this->username = $data['username'];
        $this->password = $data['password'];
        $this->role = $data['role'];
    }
}
