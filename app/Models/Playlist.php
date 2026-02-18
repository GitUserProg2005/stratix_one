<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Playlist extends Model
{
    // Разрешаем массовое заполнение этих полей
    protected $fillable = [
        'owner_id',
        'title',
    ];

    /**
     * Владелец плейлиста (пользователь)
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Треки в плейлисте
     */
    public function tracks(): BelongsToMany
    {
        return $this->belongsToMany(Track::class, 'playlist_track')
                    ->withTimestamps(); // если пивот имеет timestamps
    }
}
