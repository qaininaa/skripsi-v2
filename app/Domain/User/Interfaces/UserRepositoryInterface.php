<?php

namespace Domain\User\Interfaces;

interface UserRepositoryInterface
{
    public function getUsers();
    public function getUser($id);
}