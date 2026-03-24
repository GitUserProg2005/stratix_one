<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;


class Gigachat {
    public function sendRequest($prompt, $jsonFormat = false): string {
        $accessToken = $this->getAccessToken();

        if ($jsonFormat) {
            $prompt .= "\n\nОтвет в формате JSON. Формат: { \"answer\": \"...\" }";
        }

        $response = Http::withoutVerifying()
            ->connectTimeout((int) env('GIGACHAT_CONNECT_TIMEOUT', 30))
            ->timeout((int) env('GIGACHAT_TIMEOUT', 120))
            ->withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
            ])
            ->post('https://gigachat.devices.sberbank.ru/api/v1/chat/completions', [
                'model' => 'GigaChat',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ]
                ],
                'stream' => false,
            ]);
        
        if (!$response->successful()) {
            throw new \Exception('Failed to send request' . $response->body());
        }

        $content = $response->json('choices.0.message.content');

        if ($jsonFormat) {
            $decoded = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('JSON decode error: ' . json_last_error_msg());
            }

            if (!isset($decoded['answer'])) {
                throw new \Exception('Invalid JSON structure: ' . $content);
            }

            return $decoded['answer'];
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
}