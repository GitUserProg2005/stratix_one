<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Rate extends Model
{
    protected $fillable = [
        'title',
        'picture',
        'features',
        'access_nodes',
        'price',
    ];

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'access_nodes' => 'array',
            'price' => 'integer',
        ];
    }

    public function nodeTypes(): BelongsToMany
    {
        return $this->belongsToMany(NodeType::class, 'nodes_rate');
    }
}
