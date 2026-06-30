<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowCategory extends Model
{
    protected $fillable = ['title', 'picture'];

    public function catalogWorkflows(): HasMany
    {
        return $this->hasMany(CatalogWorkflow::class, 'category_id');
    }
}
