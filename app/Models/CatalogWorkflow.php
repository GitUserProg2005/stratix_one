<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CatalogWorkflow extends Model
{
    protected $fillable = [
        'author_id',
        'workflow_id',
        'category_id',
        'downloads',
        'title',
        'description',
        'graph',
    ];

    protected function casts(): array
    {
        return [
            'graph' => 'array',
            'downloads' => 'integer',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(WorkflowCategory::class, 'category_id');
    }
}
