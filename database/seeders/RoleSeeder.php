<?php

namespace Database\Seeders;

use App\Enums\ProjectRoleName;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Сидим фиксированные роли проекта
        foreach (ProjectRoleName::cases() as $role) {
            Role::query()->firstOrCreate(['name' => $role->value]);
        }
    }
}
