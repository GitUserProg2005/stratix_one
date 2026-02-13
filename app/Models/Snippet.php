<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Laravel\Scout\Searchable;

use App\Models\User;


class Snippet extends Model
{
    use Searchable;

    protected $fillable = [
        'track_id',
        'audio',
    ];

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->track ? $this->track->title : null, // берём title из track
            'audio' => $this->audio ? true : false,
            'track_id' => $this->track_id,
        ];
    }

    /**
     * Трек, из которого вырезан сниппет
     */
    public function track(): BelongsTo
    {
        return $this->belongsTo(Track::class);
    }

    public function tags() {
        return $this->track->tags(); 
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
     * Комментарии к сниппету
     */
    public function comments()
    {
        return $this->hasMany(Comment::class)->orderBy('created_at', 'desc');
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
