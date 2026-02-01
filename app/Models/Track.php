<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

use Illuminate\Support\Facades\Storage;

use Laravel\Scout\Searchable;


class Track extends Model
{
    use Searchable;

    protected $fillable = [
        'release_id',
        'title',
        'preview',
        'file',
        'lyrics'
    ];

    protected $casts = [
        'lyrics' => 'array'
    ];

    public function toSearchableArray()
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
        ];
    }

    /**
     * Релиз (album / ep / single)
     */
    public function release(): BelongsTo
    {
        return $this->belongsTo(Release::class);
    }

    /**
     * Теги трека
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'tag_track', 'track_id', 'tag_id')
                    ->withTimestamps();
    }
    
    /**
     * Генерируем URL к превью трека из S3
     */
    public function getPreviewUrlAttribute(): ?string
    {
        if (!$this->preview) {
            return null;
        }

        // Если уже полный URL, возвращаем как есть
        if (filter_var($this->preview, FILTER_VALIDATE_URL)) {
            return $this->preview;
        }

        // Генерируем URL из S3
        try {
            return Storage::disk('s3')->url($this->preview);
        } catch (\Exception $e) {
            \Log::warning('Failed to get preview URL from S3', [
                'preview' => $this->preview,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Генерируем URL к файлу трека из S3
     */
    public function getFileUrlAttribute(): ?string
    {
        if (!$this->file) {
            return null;
        }

        // Если уже полный URL, возвращаем как есть
        if (filter_var($this->file, FILTER_VALIDATE_URL)) {
            return $this->file;
        }

        // Генерируем URL из S3
        try {
            return Storage::disk('s3')->url($this->file);
        } catch (\Exception $e) {
            \Log::warning('Failed to get file URL from S3', [
                'file' => $this->file,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
