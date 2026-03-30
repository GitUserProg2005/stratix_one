<?php

namespace App\Services\N8N\Handles;

use App\Services\AI\Gigachat;

class AiRequest
{
    public static function handleAiRequest(Gigachat $gigachat, $node, ?string $input = null, bool $agentMode = false): string
    {
        $config = is_string($node->config)
            ? json_decode($node->config, true)
            : $node->config;

        if (! is_array($config)) {
            throw new \RuntimeException('AI node config is invalid JSON');
        }

        $prompt = $config['prompt'] ?? 'Пустой промпт';
        $outputSchema = $config['output'] ?? null;

        if ($outputSchema) {
            $prompt .= "\n\n";
            $prompt .= "ВАЖНОЕ УСЛОВИЕ:\n";
            $prompt .= "Ты ОБЯЗАН вернуть ответ СТРОГО в формате JSON.\n";
            $prompt .= "Никакого текста, пояснений или markdown — ТОЛЬКО JSON.\n";
            $prompt .= "Структура JSON должна быть строго следующей:\n\n";
            $prompt .= json_encode(
                $outputSchema,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
            );
        }

        if ($input !== null && $input !== '') {
            $prompt .= "\n\nВходные данные предыдущего шага:\n".$input;
        }

        $jsonFormat = $agentMode || (bool) $outputSchema;

        $response = $gigachat->sendRequest($prompt, $jsonFormat);

        if (is_array($response)) {
            return json_encode($response, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        }

        return (string) $response;
    }
}
