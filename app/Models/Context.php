<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Context extends Model
{
    protected $fillable = [
        'body',
        'workflow_id',
    ];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }
}
