<?php

namespace App\Services\N8N\Handles;

use App\Enums\NodeStructureSchema;
use App\Services\AI\Mistral as MistralService;
use App\Services\N8N\BaseNode;

class MistralPicture extends BaseNode
{
    public static function nodeStructureSchema(): NodeStructureSchema
    {
        return NodeStructureSchema::STATIC;
    }

    public static function inputSchema(): array
    {
        return self::group(null, [
            self::field('file', 'file', true),
            self::field('content', 'string', false),
        ]);
    }

    public static function outputSchema(): array
    {
        return self::field('content', 'string', true);
    }

    public function handle(): array
    {
        $apiKey = trim((string) $this->getConfig('api_key', ''));

        if ($apiKey === '') {
            return $this->error('API key is required');
        }

        $prompt = trim((string) $this->getConfig('prompt', ''));

        if ($prompt === '') {
            return $this->error('Prompt is required');
        }

        $path = trim((string) $this->input('file', ''));

        if ($path === '') {
            return $this->error('Input file is required');
        }

        $context = $this->input('content');
        $context = is_string($context) && trim($context) !== '' ? trim($context) : null;

        try {
            $content = app(MistralService::class)->picture($apiKey, $prompt, $path, $context, $this->getConfig());
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }

        return $this->success(['content' => $content]);
    }
}
