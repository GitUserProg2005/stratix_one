<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class Rate extends Model
{
    protected $fillable = [
        'title',
        'picture',
        'features',
        'price',
    ];

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'price' => 'integer',
        ];
    }
}
