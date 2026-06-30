<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workflow extends Model
{
    protected $fillable = ['user_id', 'name', 'meta'];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function nodes(): HasMany
    {
        return $this->hasMany(Node::class)->orderBy('order');
    }

    public function edges(): HasMany
    {
        return $this->hasMany(Edge::class);
    }

    public function dashboards(): HasMany
    {
        return $this->hasMany(Dashboard::class);
    }

    public function runs(): HasMany
    {
        return $this->hasMany(Run::class);
    }

    public function catalogEntries(): HasMany
    {
        return $this->hasMany(CatalogWorkflow::class);
    }
}
