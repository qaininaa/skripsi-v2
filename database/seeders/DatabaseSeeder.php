<?php

namespace Database\Seeders;

use Domain\User\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
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
    }
}
