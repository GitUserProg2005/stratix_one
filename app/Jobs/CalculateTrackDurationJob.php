<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\Track;
use getID3;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CalculateTrackDurationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $trackId;

    public function __construct(int $trackId)
    {
        $this->trackId = $trackId;
    }

    public function handle(): void
    {
        // вот здесь исправлено
        $track = Track::find($this->trackId);

        if (!$track || !$track->file_url) {
            Log::warning('Track not found or file URL missing', ['track_id' => $this->trackId]);
            return;
        }

        try {
            // создаём временный файл
            $tempFile = tempnam(sys_get_temp_dir(), 'track');
            
            // скачиваем с S3
            $fileContents = Storage::disk('s3')->get($track->file);
            file_put_contents($tempFile, $fileContents);

            // читаем метаданные
            $getID3 = new getID3;
            $fileInfo = $getID3->analyze($tempFile);

            // удаляем временный файл
            unlink($tempFile);

            if (!empty($fileInfo['playtime_seconds'])) {
                $track->duration = round($fileInfo['playtime_seconds'], 1);
                $track->saveQuietly();
                
                Log::info('Track duration calculated', [
                    'track_id' => $track->id,
                    'duration' => $track->duration
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to calculate track duration', [
                'track_id' => $track->id,
                'file_url' => $track->file_url,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
