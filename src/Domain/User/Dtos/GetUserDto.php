<?php

namespace Domain\User\Dtos;

class GetUserDto
{
    public $username;
    public $password;

    public function __construct(array $data)
    {
        $this->username = $data['username'];
        $this->password = $data['password'];
    }
}
