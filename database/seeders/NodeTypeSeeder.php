<?php

namespace Database\Seeders;

use App\Models\NodeType;
use Illuminate\Database\Seeder;

class NodeTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Webhook Trigger', 'type' => 'webhook_trigger', 'description' => 'Триггер вебхука'],
            ['name' => 'Condition', 'type' => 'condition', 'description' => 'Условное ветвление'],
            ['name' => 'AI Request', 'type' => 'ai_request', 'description' => 'Запрос к GigaChat'],
            ['name' => 'AI Agent Request', 'type' => 'ai_agent_request', 'description' => 'GigaChat с JSON-режимом'],
            ['name' => 'Email Report', 'type' => 'email_report', 'description' => 'Отправка результата на email'],
            ['name' => 'OSRM', 'type' => 'osrm', 'description' => 'Построение оптимального маршрута по A -> B'],
            ['name' => 'Log', 'type' => 'log', 'description' => 'Запись в лог приложения'],
            ['name' => 'Collect Metrics', 'type' => 'collect_metrics', 'description' => 'Сбор метрик (заглушка)'],
            ['name' => 'Update Metric', 'type' => 'update_metric', 'description' => 'Увеличение значений метрик (дашборд виджеты)'],
            ['name' => 'Condition', 'type' => 'condition', 'description' => 'Условное ветвление'],
            ['name' => 'Schedule', 'type' => 'schedule', 'description' => 'Расписание выполнения'],
            ['name' => 'Page Loader', 'type' => 'page_loader', 'description' => 'Загрузка страницы и конвертация в markdown'],
            ['name' => 'Go Whisper', 'type' => 'go_whisper', 'description' => 'Распознавание речи через Whisper ASR'],
            ['name' => 'Mistral Text', 'type' => 'mistral_text', 'description' => 'Текстовый запрос к Mistral AI'],
            ['name' => 'Mistral Picture', 'type' => 'mistral_picture', 'description' => 'Анализ изображения через Mistral Pixtral'],
            ['name' => 'Mistral OCR', 'type' => 'mistral_ocr', 'description' => 'OCR документов через Mistral'],
        ];

        foreach ($types as $row) {
            NodeType::firstOrCreate(
                ['type' => $row['type']],
                ['name' => $row['name'], 'description' => $row['description']]
            );
        }
    }
}
