<?php

namespace App\Jobs;

use App\Models\Snippet;
use App\Models\Track;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class CreateSnippetFromTrackJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected int $trackId
    ) {}

    protected function ffmpegAvailable(): bool
    {
        $path = PHP_OS_FAMILY === 'Windows' ? 'where ffmpeg' : 'which ffmpeg';
        return !empty(shell_exec($path));
    }

    public function handle(): void
    {
        $track = Track::find($this->trackId);

        if (!$track || !$track->file) {
            Log::warning('CreateSnippetFromTrackJob: track not found or file missing', ['track_id' => $this->trackId]);
            return;
        }

        $snippetParams = $track->snippet_parameters ?? [];
        $params = is_array($snippetParams) && isset($snippetParams[0]) && is_array($snippetParams[0])
            ? $snippetParams[0]
            : $snippetParams;
        $startTime = isset($params['start_time']) ? (float) $params['start_time'] : null;
        $duration = isset($params['duration']) ? (float) $params['duration'] : null;

        if ($startTime === null || $duration === null || $duration <= 0) {
            Log::info('CreateSnippetFromTrackJob: start_time or duration missing in snippet_parameters, skipping', [
                'track_id' => $this->trackId,
                'snippet_parameters' => $snippetParams,
            ]);
            return;
        }

        if (!$this->ffmpegAvailable()) {
            Log::warning('CreateSnippetFromTrackJob: ffmpeg not found. Install: apt install ffmpeg (or brew install ffmpeg).');
            return;
        }

        $snippet = Snippet::create(['track_id' => $track->id]);

        $workDir = null;
        $tmpInput = null;
        $tmpOutput = null;

        try {
            $workDir = storage_path('app/temp/snippet_'.$snippet->id.'_'.uniqid());
            if (!is_dir($workDir)) {
                mkdir($workDir, 0755, true);
            }

            $ext = pathinfo($track->file, PATHINFO_EXTENSION) ?: 'mp3';
            $tmpInput = $workDir.'/input.'.$ext;
            $tmpOutput = $workDir.'/snippet.mp3';

            $contents = Storage::disk('s3')->get($track->file);
            file_put_contents($tmpInput, $contents);

            $process = new Process([
                'ffmpeg',
                '-y',
                '-ss', (string) $startTime,
                '-i', $tmpInput,
                '-t', (string) $duration,
                '-acodec', 'copy',
                $tmpOutput,
            ]);

            $process->run();

            if (!$process->isSuccessful()) {
                Log::error('CreateSnippetFromTrackJob: ffmpeg failed', [
                    'snippet_id' => $snippet->id,
                    'track_id' => $track->id,
                    'output' => $process->getErrorOutput(),
                ]);
                $snippet->delete();
                return;
            }

            if (!is_file($tmpOutput)) {
                Log::error('CreateSnippetFromTrackJob: ffmpeg did not produce output file', ['snippet_id' => $snippet->id]);
                $snippet->delete();
                return;
            }

            $s3Path = 'snippets/'.$snippet->id.'/snippet.mp3';
            Storage::disk('s3')->put($s3Path, file_get_contents($tmpOutput), 'public');

            $snippet->update(['audio' => $s3Path]);

            Log::info('CreateSnippetFromTrackJob: snippet created', [
                'snippet_id' => $snippet->id,
                'track_id' => $track->id,
                's3_path' => $s3Path,
            ]);
        } catch (\Throwable $e) {
            Log::error('CreateSnippetFromTrackJob failed', [
                'track_id' => $this->trackId,
                'snippet_id' => $snippet->id ?? null,
                'error' => $e->getMessage(),
            ]);
            $snippet->delete();
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
