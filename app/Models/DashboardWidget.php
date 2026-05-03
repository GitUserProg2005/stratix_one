<?php

namespace App\Models;

use App\Enums\DashboardWidgetType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DashboardWidget extends Model
{
    protected $fillable = [
        'dashboard_id',
        'type',
        'position',
        'content',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'type' => DashboardWidgetType::class,
            'position' => 'array',
            'content' => 'array',
            'metadata' => 'array',
        ];
    }

    public function dashboard(): BelongsTo
    {
        return $this->belongsTo(Dashboard::class);
    }
}
