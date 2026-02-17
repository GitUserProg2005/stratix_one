<?php

namespace App\Jobs;

use App\Models\Track;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class ConvertTrackToHlsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected int $trackId
    ) {}

    protected function ffmpegAvailable(): bool
    {
        $path = PHP_OS_FAMILY === 'Windows' ? 'where ffmpeg' : 'which ffmpeg';
        return ! empty(shell_exec($path));
    }

    public function handle(): void
    {
        $track = Track::find($this->trackId);

        if (! $track || ! $track->file) {
            Log::warning('ConvertTrackToHlsJob: track not found or file missing', ['track_id' => $this->trackId]);
            return;
        }

        if (! $this->ffmpegAvailable()) {
            Log::warning('ConvertTrackToHlsJob: ffmpeg not found. Install: apt install ffmpeg (or brew install ffmpeg). Track will play via direct file URL.');
            return;
        }

        $workDir = null;
        $inputPath = null;

        try {
            $workDir = storage_path('app/temp/hls_'.$track->id.'_'.uniqid());
            if (! is_dir($workDir)) {
                mkdir($workDir, 0755, true);
            }

            $ext = pathinfo($track->file, PATHINFO_EXTENSION) ?: 'mp3';
            $inputPath = $workDir.'/input.'.$ext;
            $contents = Storage::disk('s3')->get($track->file);
            file_put_contents($inputPath, $contents);

            $outputDir = $workDir.'/out';
            mkdir($outputDir, 0755, true);
            $playlistPath = $outputDir.'/playlist.m3u8';
            $segmentPattern = $outputDir.'/segment_%03d.ts';

            $process = new Process([
                'ffmpeg',
                '-y',
                '-i', $inputPath,
                '-hls_time', '10',
                '-hls_playlist_type', 'vod',
                '-hls_segment_filename', $segmentPattern,
                $playlistPath,
            ]);
            
            $process->run();

            if (! $process->isSuccessful()) {
                Log::error('ConvertTrackToHlsJob: ffmpeg failed', [
                    'track_id' => $track->id,
                    'output' => $process->getErrorOutput(),
                ]);
                return;
            }

            $s3Prefix = 'tracks/'.$track->id.'/hls';
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($outputDir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($files as $file) {
                if (! $file->isFile()) {
                    continue;
                }
                $localPath = $file->getRealPath();
                $relativePath = substr($localPath, strlen($outputDir) + 1);
                $s3Key = $s3Prefix.'/'.$relativePath;
                Storage::disk('s3')->put($s3Key, file_get_contents($localPath), 'public');
            }

            $hlsPath = $s3Prefix.'/playlist.m3u8';
            $updated = DB::table('tracks')->where('id', $track->id)->update(['hls_url' => $hlsPath]);

            Log::info('ConvertTrackToHlsJob: HLS created', [
                'track_id' => $track->id,
                'hls_path' => $hlsPath,
                'rows_updated' => $updated,
            ]);
        } catch (\Throwable $e) {
            Log::error('ConvertTrackToHlsJob failed', [
                'track_id' => $this->trackId,
                'error' => $e->getMessage(),
            ]);
        } finally {
            if ($workDir && is_dir($workDir)) {
                $files = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($workDir, \RecursiveDirectoryIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::CHILD_FIRST
                );
                foreach ($files as $file) {
                    if ($file->isDir()) {
                        rmdir($file->getRealPath());
                    } else {
                        unlink($file->getRealPath());
                    }
                }
                rmdir($workDir);
            }
        }
    }
}
