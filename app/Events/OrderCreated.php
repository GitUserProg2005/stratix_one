<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Order $order;
    public int|float $timeArrival;
    public string $customerName;
    public string $geohash;

    public function __construct(Order $order, int|float $timeArrival, string $customerName, string $geohash)
    {
        $this->order = $order;
        $this->timeArrival = $timeArrival;
        $this->customerName = $customerName;
        $this->geohash = $geohash;
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('order-cells.' . $this->geohash)];
    }

    public function broadcastAs(): string
    {
        return 'order.created';
    }

    public function broadcastWith(): array
    {
        return [
            'order' => $this->order,
            'time_arrival' => $this->timeArrival,
            'customer_name' => $this->customerName,
        ];
    }
}
