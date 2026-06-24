<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


class GoWhisper
{
    public function transcribe(string $storagePath, string $contents): string
    {
        $response = Http::timeout((int) config('services.whisper.timeout', 120))
            ->retry(2, 1000)
            ->attach(
                'audio_file',
                $contents,
                basename($storagePath),
                ['Content-Type' => $this->mimeType($storagePath)]
            )
            ->post(rtrim((string) config('services.whisper.url'), '/').'/asr', [
                'model' => (string) config('services.whisper.model', 'base'),
                'language' => (string) config('services.whisper.language', 'ru'),
                'task' => 'transcribe',
                'translate' => false,
                'temperature' => 0,
            ]);

        if ($response->failed()) {
            Log::error('Whisper API request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException('Whisper API error: '.$response->status());
        }

        return $this->extractText((string) $response->body());
    }

    protected function extractText(string $body): string
    {
        $body = trim($body);
        $json = json_decode($body, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
            $text = trim((string) ($json['text'] ?? $json['transcription'] ?? ''));

            if ($text !== '') {
                return $text;
            }
        }

        return $body;
    }

    protected function mimeType(string $path): string
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'webm' => 'audio/webm',
            'wav' => 'audio/wav',
            'mp3' => 'audio/mpeg',
            'ogg' => 'audio/ogg',
            'm4a', 'mp4' => 'audio/mp4',
            'flac' => 'audio/flac',
            default => 'application/octet-stream',
        };
    }
}
