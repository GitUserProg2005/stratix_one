<?php

namespace App\Services\N8N\Handles;

use App\Services\AI\Gigachat;
use App\Services\N8N\BaseNode;

class AiRequest extends BaseNode
{
    public function handle(): string
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

        if (!empty($input)) {
            $inputString = is_array($this->input || is_object($this->input))
                ? json_encode($this->input, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                : (string) $this->input;
            $prompt .= "\n\nВходные данные предыдущего шага:\n".$inputString;
        }

        $jsonFormat = $this->node->type === 'ai_agent_request' ? true : false;

        $response = $aiService->sendRequest($prompt, $jsonFormat);

        if (is_array($response)) {
            return json_encode($response, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        }

        return (string) $response;
    }
}
