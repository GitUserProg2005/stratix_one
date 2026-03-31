<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tournament extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'prize_d_coins',
    ];

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tournament_participants', 'tournament_id', 'driver_id')
            ->withTimestamps();
    }

    public function points(): HasMany
    {
        return $this->hasMany(DriverPoint::class);
    }

    public function territories(): HasMany
    {
        return $this->hasMany(DriverTerritory::class);
    }
}

