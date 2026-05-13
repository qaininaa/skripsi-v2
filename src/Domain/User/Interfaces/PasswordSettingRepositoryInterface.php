<?php

namespace Domain\User\Interfaces;

use Domain\User\Dtos\CreateUserDto;
use Domain\User\Dtos\GetUserDto;

interface PasswordSettingRepositoryInterface
{
    public function getValue(string $key, mixed $default = null): mixed;
    public function setValue(string $key, string $value): void;
}