<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Edge extends Model
{
    protected $fillable = [
        'workflow_id',
        'source_node_id',
        'target_node_id',
        'label',
        'type',
        'data',
        'transform',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'transform' => 'array',
        ];
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    public function sourceNode(): BelongsTo
    {
        return $this->belongsTo(Node::class, 'source_node_id');
    }

    public function targetNode(): BelongsTo
    {
        return $this->belongsTo(Node::class, 'target_node_id');
    }
}
