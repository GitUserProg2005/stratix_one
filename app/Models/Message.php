<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Message extends Model
{
    protected $fillable = [
        'chat_id',
        'user_id',
        'body',
        'shareable_id',
        'shareable_type',
    ];

    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Полиморфная связь: сообщение может содержать «поделиться» (сниппет, трек и т.д.)
     */
    public function shareable(): MorphTo
    {
        return $this->morphTo();
    }
}
