<?php

namespace App\Services\N8N\Handles;

use App\Enums\NodeStructureSchema;
use App\Services\AI\GoWhisper as GoWhisperService;
use App\Services\N8N\BaseNode;

class GoWhisper extends BaseNode
{
    public static function nodeStructureSchema(): NodeStructureSchema
    {
        return NodeStructureSchema::STATIC;
    }

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
        $path = trim((string) $this->input('file', ''));

        if ($path === '') {
            return $this->error('Input file is required');
        }

        $contents = $this->fileFromPath($path);

        if ($contents === null) {
            return $this->error('File not found: '.$path);
        }

        try {
            $content = app(GoWhisperService::class)->transcribe($path, $contents);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }

        return $this->success([
            'content' => $content,
        ]);
    }
}
