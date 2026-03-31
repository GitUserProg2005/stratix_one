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
        'phone',
        'password',
        'rate_id',
        'role',
        'lat',
        'lng',
        'is_online',
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
            'role' => \App\Enums\UserRole::class,
            'lat' => 'decimal:8',
            'lng' => 'decimal:8',
            'is_online' => 'boolean',
        ];
    }

    public function rate(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Rate::class);
    }

    public function ordersAsCustomer(): HasMany
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    public function ordersAsDriver(): HasMany
    {
        return $this->hasMany(Order::class, 'driver_id');
    }

    public function vehicle(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Vehicle::class, 'driver_id');
    }

    public function tournaments(): BelongsToMany
    {
        return $this->belongsToMany(Tournament::class, 'tournament_participants', 'driver_id', 'tournament_id')
            ->withTimestamps();
    }

    public function isParticipant(int $tournamentId): bool
    {
        return $this->tournaments()->where('tournament_id', $tournamentId)->exists();
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
}
