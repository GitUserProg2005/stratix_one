<?php

use App\Enums\UserRole;
use App\Models\Order;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('workflow-step.{workflowId}', function ($user, $workflowId) {
    return $user !== null;
});

Broadcast::channel('orders.{orderId}', function ($user, $orderId) {
    $order = Order::find($orderId);
    return $order && (
        (int) $order->customer_id === (int) $user->id ||
        (int) $order->driver_id === (int) $user->id
    );
});

Broadcast::channel('drivers.{geohash}', function ($user, string $geohash) {
    return $user !== null;
});

Broadcast::channel('order-cells.{geohash}', function ($user, string $geohash) {
    if ($user === null) {
        return false;
    }
    $role = $user->role ?? null;
    return $role instanceof UserRole && in_array($role, [UserRole::Driver, UserRole::Passenger], true);
});

Broadcast::channel('tournament.{id}', function ($user, $id) {
    return $user !== null && $user->isParticipant((int) $id);
});
