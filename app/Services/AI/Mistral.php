<?php

namespace App\Services\AI;

use App\Enums\MistralModel;
use App\Services\FileStorageService;
use Illuminate\Support\Facades\Http;

class Mistral
{
    public function __construct(
        protected FileStorageService $fileStorage,
    ) {}

    public function text(string $apiKey, string $prompt, ?string $context = null, array $data = []): string
    {
        $content = $this->prompt($prompt, $context);

        $payload = array_merge([
            'model' => MistralModel::SMALL_LATEST->value,
            'messages' => [
                ['role' => 'user', 'content' => $content],
            ],
        ], $this->chatOptions($data));

        $response = $this->post($apiKey, '/chat/completions', $payload);

        return (string) $response->json('choices.0.message.content');
    }

    public function picture(string $apiKey, string $prompt, string $storagePath, ?string $context = null, array $data = []): string
    {
        $payload = array_merge([
            'model' => MistralModel::PIXTRAL_LARGE->value,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        ['type' => 'text', 'text' => $this->prompt($prompt, $context)],
                        ['type' => 'image_url', 'image_url' => $this->fileDataUri($storagePath)],
                    ],
                ],
            ],
        ], $this->chatOptions($data));

        $response = $this->post($apiKey, '/chat/completions', $payload);

        return (string) $response->json('choices.0.message.content');
    }

    public function ocr(string $apiKey, string $storagePath, array $data = []): string
    {
        $payload = array_merge([
            'model' => MistralModel::OCR_LATEST->value,
            'document' => [
                'type' => 'document_url',
                'document_url' => $this->fileDataUri($storagePath),
                'document_name' => basename($storagePath),
            ],
        ], $this->chatOptions($data));

        $response = $this->post($apiKey, '/ocr', $payload);

        $result = $response->json();

        $markdown = collect($result['pages'] ?? [])
            ->map(fn (array $page) => trim((string) ($page['markdown'] ?? '')))
            ->filter()
            ->implode("\n\n---\n\n");

        return $markdown !== '' ? $markdown : trim((string) ($result['text'] ?? ''));
    }

    protected function chatOptions(array $data): array
    {
        $options = [];

        if (isset($data['temperature']) && $data['temperature'] !== '' && $data['temperature'] !== null) {
            $options['temperature'] = (float) $data['temperature'];
        }

        if (isset($data['max_tokens']) && $data['max_tokens'] !== '' && $data['max_tokens'] !== null) {
            $options['max_tokens'] = (int) $data['max_tokens'];
        }

        return $options;
    }

    protected function post(string $apiKey, string $path, array $payload)
    {
        $response = Http::withToken($apiKey)
            ->timeout((int) config('services.mistral.timeout', 120))
            ->post(rtrim((string) config('services.mistral.base_url'), '/').$path, $payload);

        if ($response->failed()) {
            throw new \RuntimeException('Mistral request error: '.$response->status());
        }

        return $response;
    }

    protected function prompt(string $prompt, ?string $context): string
    {
        $prompt = trim($prompt);

        if ($context === null || trim($context) === '') {
            return $prompt;
        }

        return $prompt."\n\nКонтекст:\n".trim($context);
    }

    protected function fileDataUri(string $storagePath): string
    {
        $bytes = $this->fileStorage->get($storagePath);

        if ($bytes === null) {
            throw new \RuntimeException('File not found: '.$storagePath);
        }

        $extension = strtolower(pathinfo($storagePath, PATHINFO_EXTENSION));
        $mime = match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'pdf' => 'application/pdf',
            default => 'application/octet-stream',
        };

        return "data:{$mime};base64,".base64_encode($bytes);
    }
}
