<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Background extends Model
{
    protected $fillable = [
        'title',
        'picture',
    ];

    protected $appends = [
        'picture_url',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function getPictureUrlAttribute(): ?string
    {
        return $this->resolveStorageUrl($this->picture);
    }

    private function resolveStorageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        try {
            return Storage::disk('s3')->url($path);
        } catch (\Exception $e) {
            \Log::warning('Failed to get background picture URL from S3', [
                'picture' => $path,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
