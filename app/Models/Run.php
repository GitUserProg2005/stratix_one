<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Run extends Model
{
    use HasUuids;

    protected $fillable = ['workflow_id', 'nodes_runtime'];

    protected function casts(): array
    {
        return [
            'nodes_runtime' => 'array',
        ];
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    public function getExecutionTime(): int
    {
        return (int) collect($this->nodes_runtime ?? [])->sum('execution_time');
    }

    public function getAllNodes(): array
    {
        return $this->nodes_runtime ?? [];
    }
}
