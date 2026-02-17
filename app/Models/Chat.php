<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Chat extends Model
{
    protected $fillable = [];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'chat_user')->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at');
    }

    public function streak(): HasOne
    {
        return $this->hasOne(ChatStreak::class);
    }
}
