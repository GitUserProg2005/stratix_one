<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


class Release extends Model
{
    protected $fillable = [
        'artist_id',
        'title',
        'type',
    ];

    /**
     * Артист (user / artist)
     */
    public function artist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'artist_id');
    }

    /**
     * Треки релиза
     */
    public function tracks(): HasMany
    {
        return $this->hasMany(Track::class);
    }
}
