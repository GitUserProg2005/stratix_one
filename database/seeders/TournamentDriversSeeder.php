<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TournamentDriversSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('users')->updateOrInsert(
            ['id' => 2],
            [
                'name' => 'Tournament Driver 2',
                'email' => 'driver2@example.com',
                'password' => Hash::make('password'),
                'role' => UserRole::Driver->value,
                'lat' => 55.7450,
                'lng' => 37.6150,
                'is_online' => true,
                'email_verified_at' => $now,
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );

        DB::table('users')->updateOrInsert(
            ['id' => 3],
            [
                'name' => 'Tournament Driver 3',
                'email' => 'driver3@example.com',
                'password' => Hash::make('password'),
                'role' => UserRole::Driver->value,
                'lat' => 55.7470,
                'lng' => 37.6190,
                'is_online' => true,
                'email_verified_at' => $now,
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );
    }
}

