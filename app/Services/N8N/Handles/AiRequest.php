<?php

namespace App\Services\N8N\Handles;

use App\Services\AI\Gigachat;
use App\Services\N8N\BaseNode;


class AiRequest extends BaseNode
{   
    public static function inputSchema(): array {
        return self::field('content', 'string', false);
    }

    public static function outputSchema(): array {
        return self::field('content', 'string', true);
    }

    public function handle(): array
    {
        $aiService = app(Gigachat::class);

        $prompt = $this->getConfig('prompt', 'Пустой промпт');
        $prompt .= "\n\nВходные данные предыдущего шага:\n".$this->inputToString('content');

        $response = $aiService->sendRequest($prompt, false);

        return $this->success([
            'content' => (string) $response
        ]);
    }
}
