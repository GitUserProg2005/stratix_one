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
            ['name' => 'Log', 'type' => 'log', 'description' => 'Запись в лог приложения'],
            ['name' => 'Collect Metrics', 'type' => 'collect_metrics', 'description' => 'Сбор метрик (заглушка)'],
            ['name' => 'Condition', 'type' => 'condition', 'description' => 'Условное ветвление'],
        ];

        foreach ($types as $row) {
            NodeType::firstOrCreate(
                ['type' => $row['type']],
                ['name' => $row['name'], 'description' => $row['description']]
            );
        }
    }
}
