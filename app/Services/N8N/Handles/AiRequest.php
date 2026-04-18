<?php

namespace App\Services\N8N\Handles;

use App\Enums\NodeStructureSchema;
use App\Services\AI\Gigachat;
use App\Services\N8N\BaseNode;


class AiRequest extends BaseNode
{   
    public static function nodeStructureSchema(): NodeStructureSchema
    {
        return NodeStructureSchema::STATIC;
    }

    public static function outputSchema(): array {
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

        return self::field('content', 'string', true);
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

        return self::field('content', 'string', false);
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
