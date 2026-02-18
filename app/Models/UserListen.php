<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserListen extends Model
{
    public const REASONS = ['ended', 'back'];

    protected $table = 'user_listens';

    protected $fillable = [
        'user_id',
        'track_id',
        'snippet_id',
        'reason',
        'listen_time',
        'duration',
    ];

    protected $casts = [
        'reason' => 'string',
    ];

    protected $appends = ['is_completed'];

    /**
     * Трек прослушан до конца (>= 80%).
     */
    public function getIsCompletedAttribute(): bool
    {
        if ($this->duration <= 0) {
            return false;
        }
        return ($this->listen_time / $this->duration) >= 0.8;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function track(): BelongsTo
    {
        return $this->belongsTo(Track::class, 'track_id');
    }

    public function snippet(): BelongsTo
    {
        return $this->belongsTo(Snippet::class, 'snippet_id');
    }
}
