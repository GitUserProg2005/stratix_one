<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DriverPointsAdded implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $driverId,
        public int $tournamentId,
        public float $lat,
        public float $lng,
        public int $pointsCount
    ) {
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('tournament.' . $this->tournamentId)];
    }

    public function broadcastAs(): string
    {
        return 'DriverPointsAdded';
    }
}

