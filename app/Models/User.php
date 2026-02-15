<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use Illuminate\Support\Facades\Storage;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'avatar',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Accessors to append to model array/JSON.
     *
     * @var list<string>
     */
    protected $appends = [
        'avatar_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function likedSnippets(): BelongsToMany {
        return $this->belongsToMany(Snippet::class, 'liked_snippets')
            ->withTimestamps();
    }

    public function listens(): HasMany
    {
        return $this->hasMany(UserListen::class);
    }

    public function tracks()
    {
        return $this->hasManyThrough(
            Track::class,
            Release::class,
            'artist_id', 
            'release_id',
            'id',        
            'id'       
        );
    }

    // Заявки в друзья
    public function sentFriendRequests()
    {
        return $this->hasMany(Friendship::class, 'sender_id');
    }

    public function receivedFriendRequests()
    {
        return $this->hasMany(Friendship::class, 'receiver_id');
    }

    public function chats(): BelongsToMany
    {
        return $this->belongsToMany(Chat::class, 'chat_user')->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * ID друзей (взаимно принятые заявки).
     */
    public function friendIds(): array
    {
        return (array) \App\Models\Friendship::where('status', 'accepted')
            ->where(function ($q) {
                $q->where('sender_id', $this->id)->orWhere('receiver_id', $this->id);
            })
            ->get()
            ->map(fn (Friendship $f) => $f->sender_id === $this->id ? $f->receiver_id : $f->sender_id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Сниппеты, которые пользователь репостнул.
     */
    public function repostedSnippets(): BelongsToMany
    {
        return $this->belongsToMany(Snippet::class, 'reposted_snippets')->withTimestamps();
    }

    /**
     * Генерируем url к аватару пользователя
     */
    public function getAvatarUrlAttribute() : ?string {
        if (!$this->avatar) return null;

        // Если путь уже является полным URL, возвращаем как есть
        if (filter_var($this->avatar, FILTER_VALIDATE_URL)) {
            return $this->avatar;
        }

        // Генерируем URL из S3
        try {
            return Storage::disk('s3')->url($this->avatar);
        } catch (\Exception $e) {
            \Log::warning('Failed to get avatar URL from S3', [
                'avatar' => $this->avatar,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    public function getTracksCountAttribute(): int
    {
        return $this->tracks()->count();
    }

    public function getTotalDurationAttribute(): ?string
    {
        $totalSeconds = $this->tracks->sum('duration');
        if (!$totalSeconds) return null;

        $hours = floor($totalSeconds / 3600);
        $minutes = floor(($totalSeconds % 3600) / 60);
        $seconds = round($totalSeconds % 60);

        // Если часов нет, возвращаем только мм:сс
        if ($hours > 0) {
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
        } else {
            return sprintf('%02d:%02d', $minutes, $seconds);
        }
    }
}
