<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StatusProcessingUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $roomId,
        public string $thoughts,
        public bool $isBroken = false,
    ) {}

    /**
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('ai-chat-states.'.$this->roomId),
        ];
    }

    /**
     * @return array{room_id: int, thoughts: string, is_broken: bool}
     */
    public function broadcastWith(): array
    {
        return [
            'room_id' => $this->roomId,
            'thoughts' => $this->thoughts,
            'is_broken' => $this->isBroken,
        ];
    }
}
