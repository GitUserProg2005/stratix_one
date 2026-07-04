<?php

namespace Database\Seeders;

use App\Models\Rate;
use Illuminate\Database\Seeder;

class RateSeeder extends Seeder
{
    public function run(): void
    {
        $rates = [
            [
                'title' => 'Базовый',
                'price' => 500,
                'features' => [
                    ['name' => 'Email-отчёты', 'description' => 'Отправка результатов на почту'],
                    ['name' => 'Page Loader', 'description' => 'Загрузка страниц в markdown'],
                ],
            ],
            [
                'title' => 'AI',
                'price' => 1500,
                'features' => [
                    ['name' => 'GigaChat', 'description' => 'AI Request и AI Agent Request'],
                    ['name' => 'Mistral', 'description' => 'Текст, OCR и анализ изображений'],
                    ['name' => 'Whisper', 'description' => 'Распознавание речи'],
                ],
            ],
            [
                'title' => 'Geo',
                'price' => 1000,
                'features' => [
                    ['name' => 'Маршруты', 'description' => 'Построение маршрутов через OSRM'],
                    ['name' => 'PostGIS', 'description' => 'Point In Polygon'],
                ],
            ],
            [
                'title' => 'Про',
                'price' => 2500,
                'features' => [
                    ['name' => 'Все ноды', 'description' => 'Доступ ко всем платным типам нод'],
                    ['name' => 'Специализация', 'description' => 'Базовый + AI + Geo в одном тарифе'],
                ],
            ],
        ];

        foreach ($rates as $rate) {
            Rate::firstOrCreate(
                ['title' => $rate['title']],
                [
                    'price' => $rate['price'],
                    'features' => $rate['features'],
                ]
            );
        }
    }
}
