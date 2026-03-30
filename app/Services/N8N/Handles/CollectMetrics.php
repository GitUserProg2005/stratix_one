<?php

namespace App\Services\N8N\Handles;

/**
 * Заглушка: в TEST/my нет сущности Group как в доноре.
 * При появлении доменной модели — заменить на реальный сбор метрик.
 */
class CollectMetrics
{
    public static function handleCollectMetrics($node): string
    {
        $config = is_string($node->config)
            ? json_decode($node->config, true)
            : $node->config;

        $project = $config['project'] ?? null;

        return 'Collect metrics: доменная модель не подключена.'
            .($project ? " (project id в конфиге: {$project})" : '');
    }
}
