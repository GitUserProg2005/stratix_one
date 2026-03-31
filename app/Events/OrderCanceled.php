<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderCanceled implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $order;

    public function __construct($order)
    {
        $this->order = $order;
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('orders.' . $this->order->id)];
    }

    public function broadcastAs(): string
    {
        return 'order.canceled';
    }

    public function broadcastWith(): array
    {
        return ['is_canceled' => true, 'order_id' => $this->order->id];
    }
}
