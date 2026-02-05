<?php

namespace App\Jobs;

use App\Models\Track;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TranscribeTrackJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Транскрипция может занимать несколько минут — увеличиваем таймаут воркера для этого джоба.
     */
    public int $timeout = 600;

    public function __construct(
        protected int $trackId
    ) {}

    public function handle(): void
    {
        $track = Track::find($this->trackId);

        if (! $track || ! $track->file) {
            Log::warning('TranscribeTrackJob: track not found or file missing', ['track_id' => $this->trackId]);
            return;
        }

        try {
            $contents = Storage::disk('s3')->get($track->file);
            $filename = basename($track->file) ?: 'audio.mp3';

            $response = Http::timeout(120)
                ->attach('file', $contents, $filename)
                ->post(config('services.transcribe.url', 'http://127.0.0.1:8001').'/transcribe');

            if (! $response->successful()) {
                Log::error('TranscribeTrackJob: transcribe API error', [
                    'track_id' => $track->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return;
            }

            $body = $response->json();
            // Микросервис возвращает { "lyrics": [ { "start_time", "text" }, ... ] }
            $lyrics = is_array($body) && array_key_exists('lyrics', $body)
                ? $body['lyrics']
                : (is_array($body) ? $body : null);
            if (! is_array($lyrics)) {
                Log::warning('TranscribeTrackJob: unexpected response format', [
                    'track_id' => $track->id,
                    'body_keys' => is_array($body) ? array_keys($body) : null,
                ]);
                return;
            }

            // Добавляем id каждой строке и line (дубликат text для фронта)
            $lyrics = array_values(array_map(function (array $item, int $index): array {
                return array_merge($item, [
                    'id' => $index + 1,
                    'line' => $item['text'] ?? '',
                ]);
            }, $lyrics, array_keys($lyrics)));

            $track->lyrics = $lyrics;
            $track->saveQuietly();

            Log::info('TranscribeTrackJob: lyrics saved', [
                'track_id' => $track->id,
                'segments' => count($lyrics),
            ]);
        } catch (\Throwable $e) {
            Log::error('TranscribeTrackJob failed', [
                'track_id' => $this->trackId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
