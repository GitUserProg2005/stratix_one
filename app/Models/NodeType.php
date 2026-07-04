<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class NodeType extends Model
{
    protected $fillable = [
        'name',
        'type',
        'description',
    ];

    public function rates(): BelongsToMany
    {
        return $this->belongsToMany(Rate::class, 'nodes_rate');
    }
}
