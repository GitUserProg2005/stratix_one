<?php

namespace Database\Seeders;

use App\Models\WorkflowCategory;
use Illuminate\Database\Seeder;

class WorkflowCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['title' => 'AI-агент', 'picture' => null],
            ['title' => 'Web-скраперы', 'picture' => null],
            ['title' => 'Интеграции', 'picture' => null],
            ['title' => 'Автоматизация', 'picture' => null],
        ];

        foreach ($categories as $category) {
            WorkflowCategory::firstOrCreate(
                ['title' => $category['title']],
                ['picture' => $category['picture']],
            );
        }
    }
}
