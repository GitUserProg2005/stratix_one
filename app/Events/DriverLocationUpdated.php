<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DriverLocationUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $driver;
    public string $geohash;

    public function __construct(User $driver, string $geohash)
    {
        $this->driver = $driver;
        $this->geohash = $geohash;
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('drivers.' . $this->geohash)];
    }

    public function broadcastAs(): string
    {
        return 'DriverLocationUpdated';
    }

    public function broadcastWith(): array
    {
        $driver = $this->driver;
        $driver->loadMissing('vehicle');
        $vehicle = $driver->vehicle;

        return [
            'driver' => [
                'id' => $driver->id,
                'name' => $driver->name,
                'phone' => $driver->phone,
                'avatar_url' => $driver->avatar_url,
                'picture_url' => $driver->avatar_url,
                'lat' => $driver->lat ? (float) $driver->lat : null,
                'lng' => $driver->lng ? (float) $driver->lng : null,
                'vehicle' => $vehicle ? [
                    'picture_url' => $vehicle->picture_url,
                    'license_plate' => $vehicle->license_plate,
                    'brand' => $vehicle->brand,
                    'model' => $vehicle->model,
                    'color' => $vehicle->color,
                ] : null,
            ],
        ];
    }
}
