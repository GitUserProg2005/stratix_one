<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Workflow;
use Database\Seeders\Support\WorkflowSeedData;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class WorkflowSeeder extends Seeder
{
    public function run(): void
    {
        foreach (WorkflowSeedData::users() as $email => $attrs) {
            User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $attrs['name'],
                    'password' => Hash::make('password'),
                ],
            );
        }

        $usersByEmail = User::query()
            ->whereIn('email', array_keys(WorkflowSeedData::users()))
            ->pluck('id', 'email');

        foreach (WorkflowSeedData::workflows() as $definition) {
            $userId = $usersByEmail[$definition['user_email']] ?? null;

            if (! $userId) {
                continue;
            }

            Workflow::updateOrCreate(
                [
                    'name' => $definition['name'],
                    'user_id' => $userId,
                ],
                [
                    'meta' => $definition['meta'],
                ],
            );
        }
    }
}
