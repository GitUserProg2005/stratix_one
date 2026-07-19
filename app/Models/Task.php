<?php

namespace App\Models;

use App\Enums\TaskDifficulty;
use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Kalnoy\Nestedset\NodeTrait;

class Task extends Model
{
    use NodeTrait;

    protected $fillable = [
        'project_id',
        'parent_id',
        'title',
        'due_at',
        'status',
        'difficulty',
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'status' => TaskStatus::class,
            'difficulty' => TaskDifficulty::class,
        ];
    }

    // Деревья задач изолированы по проекту
    protected function getScopeAttributes(): array
    {
        return ['project_id'];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function workers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'task_workers')
            ->withTimestamps();
    }
}
