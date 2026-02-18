<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comment extends Model
{
    protected $fillable = [
        'snippet_id',
        'user_id',
        'content',
    ];

    /**
     * Сниппет, к которому относится комментарий
     */
    public function snippet(): BelongsTo
    {
        return $this->belongsTo(Snippet::class);
    }

    /**
     * Пользователь, который оставил комментарий
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
