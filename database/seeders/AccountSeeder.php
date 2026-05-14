<?php

namespace Database\Seeders;

use Domain\User\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        User::create([
            'name' => 'Super Admin',
            'username' => 'superadmin',
            'role' => 'super',
            'password' => Hash::make('admin123'),
            'password_changed_at' => $now,
        ]);

        User::create([
            'name' => 'Admin QC',
            'username' => 'adminqc',
            'role' => 'admin',
            'password' => Hash::make('admin123'),
            'password_changed_at' => $now,
        ]);

        User::create([
            'name' => 'Analis 1',
            'username' => 'analis1',
            'role' => 'analyst',
            'password' => Hash::make('admin123'),
            'password_changed_at' => $now,
        ]);

        User::create([
            'name' => 'Analis 2',
            'username' => 'analis2',
            'role' => 'analis',
            'password' => Hash::make('admin123'),
            'password_changed_at' => $now,
        ]);

        User::create([
            'name' => 'Analis 3',
            'username' => 'analis3',
            'role' => 'analyst',
            'password' => Hash::make('admin123'),
            'password_changed_at' => $now,
        ]);

        User::create([
            'name' => 'Analis 4',
            'username' => 'analis4',
            'role' => 'analyst',
            'password' => Hash::make('admin123'),
            'password_changed_at' => $now,
        ]);
    }
}
