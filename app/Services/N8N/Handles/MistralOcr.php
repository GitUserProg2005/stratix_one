<?php

namespace App\Services\N8N\Handles;

use App\Services\AI\Mistral as MistralService;
use App\Services\N8N\BaseNode;


class MistralOcr extends BaseNode
{
    public static function inputSchema(): array
    {
        return self::field('file', 'file', true);
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

        $path = trim((string) $this->input('file', ''));

        if ($path === '') {
            return $this->error('Input file is required');
        }

        try {
            $content = app(MistralService::class)->ocr($apiKey, $path, $this->getConfig());
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }

        return $this->success(['content' => $content]);
    }
}
