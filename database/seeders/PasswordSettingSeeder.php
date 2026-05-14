<?php

namespace Database\Seeders;

use Domain\PasswordPolicy\Interfaces\PasswordSettingRepositoryInterface;
use Illuminate\Database\Seeder;

class PasswordSettingSeeder extends Seeder
{
    public function run(): void
    {
        /** @var PasswordSettingRepositoryInterface $repository */
        $repository = app(PasswordSettingRepositoryInterface::class);

        $repository->setValue('password_expiration_days', '90');
        $repository->setValue('password_history_count', '3');
    }
}
