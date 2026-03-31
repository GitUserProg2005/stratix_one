<?php

namespace Database\Seeders;

use App\Models\Tournament;
use Illuminate\Database\Seeder;

class TournamentSeeder extends Seeder
{
    public function run(): void
    {
        Tournament::query()->updateOrCreate(
            ['name' => 'Рождественский турнир'],
            ['prize_d_coins' => 500]
        );
    }
}

