<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    protected $fillable = [
        'point_a',
        'point_b',
        'customer_id',
        'driver_id',
        'pickup_lat',
        'pickup_lng',
        'pickup_address',
        'destination_lat',
        'destination_lng',
        'destination_address',
        'price',
        'distance_pickup',
        'time_pickup',
        'status',
        'accepted_at',
        'arrived_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'price' => 'decimal:2',
            'distance_pickup' => 'decimal:2',
            'time_pickup' => 'integer',
            'pickup_lat' => 'decimal:8',
            'pickup_lng' => 'decimal:8',
            'destination_lat' => 'decimal:8',
            'destination_lng' => 'decimal:8',
            'accepted_at' => 'datetime',
            'arrived_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }
}
