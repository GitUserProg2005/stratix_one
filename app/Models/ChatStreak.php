<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatStreak extends Model
{
    protected $fillable = [
        'chat_id',
        'days',
        'last_activity_at',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'last_activity_at' => 'datetime',
            'active' => 'boolean',
        ];
    }

    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class);
    }
}
