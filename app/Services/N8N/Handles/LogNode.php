<?php

namespace App\Services\N8N\Handles;

use Illuminate\Support\Facades\Log;

class LogNode
{
    public static function handle($node, $previousResult): string
    {
        $config = is_string($node->config)
            ? json_decode($node->config, true)
            : $node->config;

        $label = is_array($config) ? ($config['message'] ?? $node->title ?? 'log') : ($node->title ?? 'log');
        $payload = is_string($previousResult) ? $previousResult : json_encode($previousResult ?? '');

        Log::info('[Workflow log node] '.$label, ['body' => $payload]);

        return (string) ($previousResult ?? '');
    }
}
