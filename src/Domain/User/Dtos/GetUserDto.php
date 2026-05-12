<?php

namespace Domain\User\Dtos;

class GetUserDto
{
    public $username;

    public function __construct(array $data)
    {
        $this->username = $data['username'];
    }
}
