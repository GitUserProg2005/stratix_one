<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Purchace extends Model
{
    protected $table = 'purchases';

    protected $fillable = [
        'user_id',
        'payment_link',
        'payment_id',
        'is_paid',
    ];

    protected function casts(): array
    {
        return [
            'is_paid' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
