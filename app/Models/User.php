<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

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
        'background',
        'email',
        'phone',
        'password',
        'rate_id',
        'role',
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
        'background_url',
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
            'role' => \App\Enums\UserRole::class,
        ];
    }

    public function rate(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Rate::class);
    }

    public function rooms(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\Room::class, 'owner_id');
    }

    public function messages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\Message::class);
    }

    public function createdDashboards(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\Dashboard::class, 'creator_id');
    }

    public function catalogWorkflows(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\CatalogWorkflow::class, 'author_id');
    }

    /**
     * Генерируем url к аватару пользователя
     */
    public function getAvatarUrlAttribute(): ?string
    {
        return $this->resolveStorageUrl($this->avatar, 'avatar');
    }

    /**
     * Генерируем url к фону профиля
     */
    public function getBackgroundUrlAttribute(): ?string
    {
        return $this->resolveStorageUrl($this->background, 'background');
    }

    private function resolveStorageUrl(?string $path, string $field): ?string
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
            \Log::warning("Failed to get {$field} URL from S3", [
                $field => $path,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
