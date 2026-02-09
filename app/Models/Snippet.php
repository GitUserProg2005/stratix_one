<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

use App\Models\User;


class Snippet extends Model
{
    protected $fillable = [
        'track_id',
        'audio',
    ];

    /**
     * Трек, из которого вырезан сниппет
     */
    public function track(): BelongsTo
    {
        return $this->belongsTo(Track::class);
    }

    public function likedBy()
    {
        return $this->belongsToMany(User::class, 'liked_snippets')
                    ->withTimestamps();
    }

    public function isLikedBy(User $user) {
        if (!$user) return false;
        
        return $this->likedBy()->where('user_id', $user->id)->exists();
    }

    public function likesCount() {
        return $this->likedBy()->count();
    }

    /**
     * URL аудио сниппета из S3
     */
    public function getAudioUrlAttribute(): ?string
    {
        if (!$this->audio) {
            return null;
        }

        if (filter_var($this->audio, FILTER_VALIDATE_URL)) {
            return $this->audio;
        }

        try {
            return Storage::disk('s3')->url($this->audio);
        } catch (\Exception $e) {
            \Log::warning('Failed to get snippet audio URL from S3', [
                'snippet_id' => $this->id,
                'audio' => $this->audio,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
