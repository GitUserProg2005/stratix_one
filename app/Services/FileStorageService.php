<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;


class FileStorageService
{
    private const WORKFLOW_PREFIX = 'workflows/';

    public function storeUploadedFile(UploadedFile $file): ?string
    {
        $extension = $file->getClientOriginalExtension() ?: 'bin';
        $path = self::WORKFLOW_PREFIX.uniqid().'.'.$extension;

        try {
            $uploaded = Storage::disk('s3')->put(
                $path,
                file_get_contents($file->getRealPath())
            );

            return $uploaded ? $path : null;
        } catch (\Throwable) {
            return null;
        }
    }

    public function storeFromUrl(string $url): ?string
    {
        try {
            $response = Http::timeout(30)->get($url);

            if (! $response->successful()) {
                return null;
            }

            $urlPath = parse_url($url, PHP_URL_PATH) ?? '';
            $extension = pathinfo($urlPath, PATHINFO_EXTENSION) ?: 'bin';
            $path = self::WORKFLOW_PREFIX.uniqid().'.'.$extension;

            $uploaded = Storage::disk('s3')->put($path, $response->body());

            return $uploaded ? $path : null;
        } catch (\Throwable) {
            return null;
        }
    }

    public function exists(string $path): bool
    {
        try {
            return Storage::disk('s3')->exists($path);
        } catch (\Throwable) {
            return false;
        }
    }

    public function get(string $path): ?string
    {
        if (! $this->exists($path)) {
            return null;
        }

        try {
            return Storage::disk('s3')->get($path);
        } catch (\Throwable) {
            return null;
        }
    }
}
