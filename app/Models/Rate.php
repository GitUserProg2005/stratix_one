<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

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

    public function pictureUrl(): ?string
    {
        if (!$this->picture) {
            return null;
        }

        $picture = preg_replace('/\.png$/i', '.svg', $this->picture);

        if (filter_var($picture, FILTER_VALIDATE_URL)) {
            return $picture;
        }

        if (str_starts_with($picture, '/')) {
            return asset($picture);
        }

        return Storage::disk('s3')->url($picture);
    }
}
