<?php

namespace App\Services\N8N\Handles;

use App\Enums\NodeStructureSchema;
use App\Services\AI\Gigachat;
use App\Services\N8N\BaseNode;


class AiAgentRequest extends BaseNode
{
    public static function nodeStructureSchema(): NodeStructureSchema
    {
        return NodeStructureSchema::DYNAMIC;
    }

    protected function dynamicOutputSchema(): ?array
    {
        return $this->getConfig('output');
    }

    public static function inputSchema(): array {
        // return [
        //     'type' => 'group',
        //     'name' => 'root',
        //     'fields' => [
        //         [
        //             'type' => 'field',
        //             'key' => 'content',
        //             'data_type' => 'string'
        //         ]
        //     ]
        // ];

        return self::field('content');
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

        $input = $this->inputToString('content');
        $prompt .= "\n\nВходные данные предыдущего шага:\n".$input;

        $response = $aiService->sendRequest($prompt, true);

        if (is_array($response)) {
            return $this->success([
                'content' => $response
            ]);
        }

        return $this->error('AI service error: '.$response);
    }
}
