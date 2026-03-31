<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Vehicle extends Model
{
    protected $fillable = [
        'driver_id',
        'picture',
        'brand',
        'model',
        'color',
        'license_plate',
    ];

    protected $appends = ['picture_url'];

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function getPictureUrlAttribute(): ?string
    {
        if (! $this->picture) {
            return null;
        }

        if (filter_var($this->picture, FILTER_VALIDATE_URL)) {
            return $this->picture;
        }

        try {
            return Storage::disk('s3')->url($this->picture);
        } catch (\Exception) {
            return null;
        }
    }
}
