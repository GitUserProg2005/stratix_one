<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    protected $fillable = [
        'name',
        'interested'
    ];

    /**
     * Связь многие ко многим с треками
     */
    public function tracks(): BelongsToMany
    {
        return $this->belongsToMany(Track::class, 'tag_track', 'tag_id', 'track_id')
                    ->withTimestamps();
    }
}
