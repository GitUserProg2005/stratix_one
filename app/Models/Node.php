<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Node extends Model
{
    protected $fillable = ['workflow_id', 'type', 'order', 'title', 'config', 'position'];

    protected function casts(): array
    {
        return [
            'config' => 'array',
            'position' => 'array',
        ];
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    public function edges(): HasMany
    {
        return $this->hasMany(Edge::class, 'source_node_id');
    }
}
