<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    protected $fillable = [
        'title',
        'owner_id',
        'context_id',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function context(): BelongsTo
    {
        return $this->belongsTo(Context::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}
