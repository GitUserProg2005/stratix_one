<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderAccepted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Order $order;
    public array $driver;

    public function __construct(Order $order, array $driver)
    {
        $this->order = $order;
        $this->driver = $driver;
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('orders.' . $this->order->id)];
    }

    public function broadcastAs(): string
    {
        return 'order.accepted';
    }

    public function broadcastWith(): array
    {
        return ['order' => $this->order, 'driver' => $this->driver];
    }
}
