<?php

namespace App\Services\N8N\Handles;

use App\Services\AI\Gigachat;
use App\Services\N8N\BaseNode;


class AiAgentRequest extends BaseNode
{
    public static function inputSchema(): array {
        return [
            'content' => 'string',
        ];
    }

    public static function outputSchema(): array {
        return [
            'content' => 'array'
        ];
    }

    public function handle(): array
    {
        $aiService = app(Gigachat::class);

        $prompt = $this->getConfig('prompt', 'Пустой промпт');
        $outputSchema = $this->getConfig('output');

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
        
        $prompt .= "\n\nВходные данные предыдущего шага:\n".$this->input('content', 'NONE');

        $response = $aiService->sendRequest($prompt, true);

        if (is_array($response)) {
            return $this->success([
                'content' => $response
            ]);
        }

        return $this->error('AI service error: '.$response);
    }
}
