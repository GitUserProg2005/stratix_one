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
                'title' => 'Ignium',
                'price' => 299,
                'picture' => '/images/pricing/rate-level-1.svg',
                'features' => [
                    ['name' => 'Dashboard', 'description' => 'Дашборды и виджеты метрик'],
                    ['name' => 'Schedule', 'description' => 'Cron-нода для запуска workflow по расписанию'],
                    ['name' => 'Старт автоматизации', 'description' => 'Базовый тариф для первых сценариев'],
                ],
            ],
            [
                'title' => 'Polium',
                'price' => 1499,
                'picture' => '/images/pricing/rate-level-2.svg',
                'features' => [
                    ['name' => 'CRM', 'description' => 'Воронки и работа с клиентскими сценариями'],
                    ['name' => 'Ignium', 'description' => 'Все возможности стартового тарифа'],
                    ['name' => 'OSRM + Whisper', 'description' => 'Маршруты и распознавание речи'],
                ],
            ],
            [
                'title' => 'Hornium',
                'price' => 2999,
                'picture' => '/images/pricing/rate-level-3.svg',
                'features' => [
                    ['name' => 'AI Agent', 'description' => 'GigaChat Agent и полный AI-стек'],
                    ['name' => 'Polium', 'description' => 'Все возможности среднего тарифа'],
                    ['name' => 'Mistral', 'description' => 'Текст, OCR и анализ изображений'],
                ],
            ],
        ];

        foreach ($rates as $rate) {
            Rate::updateOrCreate(
                ['title' => $rate['title']],
                [
                    'price' => $rate['price'],
                    'picture' => $rate['picture'],
                    'features' => $rate['features'],
                ]
            );
        }
    }
}
