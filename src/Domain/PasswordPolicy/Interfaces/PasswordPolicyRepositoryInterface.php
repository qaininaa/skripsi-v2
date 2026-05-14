<?php

namespace Domain\PasswordPolicy\Interfaces;

interface PasswordPolicyRepositoryInterface
{
    public function getAll(): array;
    public function getValue(string $key, mixed $default = null): mixed;
    public function setValue(string $key, string $value): void;
}
