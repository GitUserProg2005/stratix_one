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

class CalculateTrackAudioParametersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Таймаут воркера: загрузка с S3 и анализ могут занимать несколько минут. */
    public int $timeout = 600;

    protected int $trackId;

    public function __construct(int $trackId)
    {
        $this->trackId = $trackId;
    }

    public function handle(): void
    {
        $track = Track::find($this->trackId);

        if (!$track || !$track->file) {
            Log::warning('Track not found or file missing', ['track_id' => $this->trackId]);
            return;
        }

        $baseUrl = config('services.audio_analysis.url');
        if (!$baseUrl) {
            Log::warning('Audio analysis service URL not configured');
            return;
        }

        $stream = null;
        try {
            $stream = Storage::disk('s3')->readStream($track->file);
            if (!is_resource($stream)) {
                Log::warning('Cannot open track file stream', ['track_id' => $track->id, 'file' => $track->file]);
                return;
            }

            $filename = basename($track->file) ?: 'audio.mp3';
            $fileField = config('services.audio_analysis.file_field', 'file');

            $response = Http::timeout(300)
                ->attach($fileField, $stream, $filename, ['Content-Type' => 'audio/mpeg'])
                ->post(rtrim($baseUrl, '/') . '/analyze');

            if (!$response->successful()) {
                Log::error('Audio analysis service error', [
                    'track_id' => $track->id,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return;
            }

            $data = $response->json();
            $duration = isset($data['duration']) ? (int) $data['duration'] : null;
            $energy = isset($data['energy']) ? (float) $data['energy'] : null;
            $centroid = isset($data['centroid']) ? (float) $data['centroid'] : null;

            $track->duration = $duration;
            $parameters = array_filter([
                'energy' => $energy,
                'centroid' => $centroid,
            ], fn ($v) => $v !== null);

            $track->parameters = !empty($parameters) ? $parameters : null;
            $track->saveQuietly();

            Log::info('Track audio parameters calculated', [
                'track_id' => $track->id,
                'duration' => $track->duration,
                'parameters' => $track->parameters,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to calculate track audio parameters', [
                'track_id' => $track->id,
                'file' => $track->file,
                'error' => $e->getMessage(),
            ]);
        } finally {
            if (is_resource($stream ?? null)) {
                fclose($stream);
            }
        }
    }
}
