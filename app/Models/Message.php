<?php

namespace App\Models;

use App\Enums\MessageRole;
use App\Enums\MessageType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $fillable = [
        'text',
        'role',
        'type',
        'user_id',
        'room_id',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'role' => MessageRole::class,
            'type' => MessageType::class,
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }
}
