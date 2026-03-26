<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;


class Gigachat {
    public function sendRequest($prompt, $jsonFormat = false): mixed {
        $accessToken = $this->getAccessToken();
        $messages = [
            [
                'role' => 'system',
                'content' => 'Ты AI-ассистент. Отвечай кратко и по делу.',
            ],
            [
                'role' => 'user',
                'content' => $prompt,
            ],
        ];

        if ($jsonFormat) {
            $messages[0]['content'] = 'Ты AI-ассистент. Отвечай строго валидным JSON без markdown, без пояснений и без дополнительного текста.';
        }

        $payload = [
            'model' => 'GigaChat',
            'messages' => $messages,
            'stream' => false,
            'temperature' => 0,
        ];

        if ($jsonFormat) {
            $payload['response_format'] = [
                'type' => 'json_object',
            ];
        }

        $response = $this->requestCompletion($accessToken, $payload);
        
        if (!$response->successful()) {
            throw new \Exception('Failed to send request' . $response->body());
        }

        $content = $response->json('choices.0.message.content');

        if ($jsonFormat) {
            return $this->decodeJsonResponse($content);
        }

        return $content;
    }

    public function getAccessToken(): string {
        return Cache::remember('gigachat_access_token', now()->addMinutes(25), function () {
            return $this->requestNewAccessToken();
        });
    }

    protected function requestNewAccessToken(): string {
        $response = Http::withoutVerifying()
            ->connectTimeout((int) env('GIGACHAT_CONNECT_TIMEOUT', 30))
            ->timeout((int) env('GIGACHAT_TIMEOUT', 120))
            ->withHeaders([
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept' => 'application/json',
                'RqUID' => (string) \Str::uuid(),
                'Authorization' => 'Basic ' . env('GIGACHAT_AUTHORIZED_KEY'),
            ])
            ->asForm()
            ->post('https://ngw.devices.sberbank.ru:9443/api/v2/oauth', [
                'scope' => 'GIGACHAT_API_PERS'
            ]);
        
        if (!$response->successful()) {
            throw new \Exception('Failed to get access token' . $response->body());
        }

        return $response->json('access_token');
    }

    protected function decodeJsonResponse(string $content): mixed
    {
        $decoded = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        // Fallback: model may wrap JSON in markdown fences.
        $cleaned = trim($content);
        $cleaned = preg_replace('/^```(?:json)?\s*/i', '', $cleaned) ?? $cleaned;
        $cleaned = preg_replace('/\s*```$/', '', $cleaned) ?? $cleaned;

        $decoded = json_decode($cleaned, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        // Fallback: extract JSON object/array fragment from text.
        if (preg_match('/(\{.*\}|\[.*\])/s', $cleaned, $matches) === 1) {
            $decoded = json_decode($matches[1], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return $this->repairJsonWithModel($content);
    }

    protected function requestCompletion(string $accessToken, array $payload)
    {
        return Http::withoutVerifying()
            ->connectTimeout((int) env('GIGACHAT_CONNECT_TIMEOUT', 30))
            ->timeout((int) env('GIGACHAT_TIMEOUT', 120))
            ->withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
            ])
            ->post('https://gigachat.devices.sberbank.ru/api/v1/chat/completions', $payload);
    }

    protected function repairJsonWithModel(string $rawContent): mixed
    {
        $accessToken = $this->getAccessToken();

        $repairPayload = [
            'model' => 'GigaChat',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Преобразуй входной текст в строго валидный JSON. Верни только JSON без markdown и без комментариев.',
                ],
                [
                    'role' => 'user',
                    'content' => $rawContent,
                ],
            ],
            'stream' => false,
            'temperature' => 0,
            'response_format' => [
                'type' => 'json_object',
            ],
        ];

        $repairResponse = $this->requestCompletion($accessToken, $repairPayload);
        if (!$repairResponse->successful()) {
            throw new \Exception('JSON decode error: ' . json_last_error_msg() . '. Raw: ' . mb_substr($rawContent, 0, 500));
        }

        $repairedContent = (string) $repairResponse->json('choices.0.message.content');
        $decoded = json_decode($repairedContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('JSON decode error: ' . json_last_error_msg() . '. Raw: ' . mb_substr($rawContent, 0, 500));
        }

        return $decoded;
    }
}