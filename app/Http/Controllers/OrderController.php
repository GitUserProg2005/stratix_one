<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Events\OrderAccepted;
use App\Events\OrderArrived;
use App\Events\OrderCanceled;
use App\Events\OrderCompleted;
use App\Events\OrderCreated;
use App\Events\OrderInWay;
use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Sk\Geohash\Geohash;

class OrderController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $orders = Order::query()
            ->where('customer_id', $user->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Order $order) => [
                'id' => $order->id,
                'pickup_address' => $order->pickup_address,
                'destination_address' => $order->destination_address,
                'price' => $order->price,
                'status' => $order->status->value,
                'created_at' => $order->created_at?->toIso8601String(),
                'completed_at' => $order->completed_at?->toIso8601String(),
            ]);

        return Inertia::render('Orders/Index', [
            'orders' => $orders,
            'headerStatic' => true,
        ]);
    }

    public function create(Request $request)
    {
        $validated = $request->validate([
            'point_a' => 'required|array',
            'point_a.lat' => 'required|numeric',
            'point_a.lng' => 'required|numeric',
            'point_a.display_name' => ['nullable', 'string', 'max:1000'],
            'point_b' => 'required|array',
            'point_b.lat' => 'required|numeric',
            'point_b.lng' => 'required|numeric',
            'point_b.display_name' => ['nullable', 'string', 'max:1000'],
            'price' => 'nullable|numeric|min:0',
            'time_arrival' => 'required|numeric',
        ]);

        $user = $request->user();
        $order = Order::create([
            // Backward compatibility: existing DB still has NOT NULL point_a/point_b.
            'point_a' => ($validated['point_a']['display_name'] ?? null)
                ?: ($validated['point_a']['lat'] . ',' . $validated['point_a']['lng']),
            'point_b' => ($validated['point_b']['display_name'] ?? null)
                ?: ($validated['point_b']['lat'] . ',' . $validated['point_b']['lng']),
            'customer_id' => $user->id,
            'driver_id' => null,
            'pickup_lat' => $validated['point_a']['lat'],
            'pickup_lng' => $validated['point_a']['lng'],
            'pickup_address' => $validated['point_a']['display_name'] ?? null,
            'destination_lat' => $validated['point_b']['lat'],
            'destination_lng' => $validated['point_b']['lng'],
            'destination_address' => $validated['point_b']['display_name'] ?? null,
            'price' => $validated['price'],
            'status' => OrderStatus::Pending,
        ]);

        $geohash = (new Geohash())->encode(
            (float) $validated['point_a']['lat'],
            (float) $validated['point_a']['lng'],
            6
        );

        event(new OrderCreated($order, $validated['time_arrival'], $user->name, $geohash));

        return response()->json(['success' => true, 'order' => $order], 201);
    }

    public function accept(Request $request)
    {
        $validated = $request->validate([
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'distance' => ['required', 'numeric', 'min:0'],
            'duration' => ['required', 'numeric', 'min:0'],
        ]);

        $user = $request->user();
        if ($user->role !== \App\Enums\UserRole::Driver) {
            return response()->json(['success' => false, 'message' => 'Only driver can accept order'], 403);
        }

        $order = Order::findOrFail($validated['order_id']);
        if ($order->driver_id !== null) {
            return response()->json(['success' => false, 'message' => 'Order already accepted'], 422);
        }

        $order->update([
            'driver_id' => $user->id,
            'status' => OrderStatus::Accepted,
            'accepted_at' => now(),
            'distance_pickup' => $validated['distance'],
            'time_pickup' => (int) $validated['duration'],
        ]);

        $user->load('vehicle');
        $vehicle = $user->vehicle;
        $driver = [
            'username' => $user->name,
            'phone' => $user->phone,
            'lat' => $user->lat ? (float) $user->lat : null,
            'lng' => $user->lng ? (float) $user->lng : null,
            'distance' => $validated['distance'],
            'duration' => $validated['duration'],
            'vehicle' => $vehicle ? [
                'picture_url' => $vehicle->picture_url,
                'license_plate' => $vehicle->license_plate,
                'brand' => $vehicle->brand,
                'model' => $vehicle->model,
                'color' => $vehicle->color,
            ] : null,
        ];

        event(new OrderAccepted($order->fresh(), $driver));

        return response()->json([
            'success' => true,
            'order' => $order->fresh()->load('customer'),
        ]);
    }

    public function arrived(Request $request)
    {
        $validated = $request->validate([
            'order_id' => ['required', 'integer', 'exists:orders,id'],
        ]);

        $user = $request->user();
        if ($user->role !== \App\Enums\UserRole::Driver) {
            return response()->json(['success' => false, 'message' => 'Only driver can set arrived'], 403);
        }

        $order = Order::findOrFail($validated['order_id']);
        if ((int) $order->driver_id !== (int) $user->id) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $order->update([
            'status' => OrderStatus::Arrived,
            'arrived_at' => now(),
        ]);

        event(new OrderArrived($order->fresh()));
        return response()->json(['success' => true, 'order' => $order->fresh()]);
    }

    public function inWay(Request $request)
    {
        $validated = $request->validate([
            'order_id' => ['required', 'integer', 'exists:orders,id'],
        ]);

        $user = $request->user();
        if ($user->role !== \App\Enums\UserRole::Driver) {
            return response()->json(['success' => false, 'message' => 'Only driver can set in-way'], 403);
        }

        $order = Order::findOrFail($validated['order_id']);
        if ((int) $order->driver_id !== (int) $user->id) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $order->update(['status' => OrderStatus::InProgress]);
        event(new OrderInWay($order->fresh()));
        return response()->json(['success' => true, 'order' => $order->fresh()]);
    }

    public function complete(Request $request)
    {
        $validated = $request->validate([
            'order_id' => ['required', 'integer', 'exists:orders,id'],
        ]);

        $user = $request->user();
        if ($user->role !== \App\Enums\UserRole::Driver) {
            return response()->json(['success' => false, 'message' => 'Only driver can complete order'], 403);
        }

        $order = Order::findOrFail($validated['order_id']);
        if ((int) $order->driver_id !== (int) $user->id) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $order->update([
            'status' => OrderStatus::Completed,
            'completed_at' => now(),
        ]);

        event(new OrderCompleted($order->fresh()));
        return response()->json(['success' => true, 'order' => $order->fresh()]);
    }

    public function cancel(Request $request)
    {
        $validated = $request->validate([
            'order_id' => ['required', 'integer', 'exists:orders,id'],
        ]);

        $user = $request->user();
        $order = Order::findOrFail($validated['order_id']);
        if ((int) $order->customer_id !== (int) $user->id) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        if (in_array($order->status, [OrderStatus::Completed, OrderStatus::Cancelled], true)) {
            return response()->json(['success' => false, 'message' => 'Order already closed'], 422);
        }

        $order->update(['status' => OrderStatus::Cancelled]);
        event(new OrderCanceled($order->fresh()));
        return response()->json(['success' => true]);
    }

    public function getOrder(Request $request)
    {
        $user = $request->user();
        $order = Order::query()
            ->where(function ($q) use ($user) {
                $q->where('customer_id', $user->id)->orWhere('driver_id', $user->id);
            })
            ->whereIn('status', [
                OrderStatus::Pending,
                OrderStatus::Accepted,
                OrderStatus::Arrived,
                OrderStatus::InProgress,
            ])
            ->orderByDesc('created_at')
            ->with(['driver.vehicle', 'customer'])
            ->first();

        if (! $order) {
            return response()->json(['success' => false, 'order' => null, 'driver' => null]);
        }

        $driver = null;
        if ($order->driver) {
            $d = $order->driver;
            $d->loadMissing('vehicle');
            $vehicle = $d->vehicle;
            $driver = [
                'id' => $d->id,
                'username' => $d->name,
                'phone' => $d->phone,
                'lat' => $d->lat !== null ? (float) $d->lat : null,
                'lng' => $d->lng !== null ? (float) $d->lng : null,
                'avatar_url' => $d->avatar_url,
                'distance' => $order->distance_pickup !== null ? (float) $order->distance_pickup : null,
                'duration' => $order->time_pickup !== null ? (int) ceil($order->time_pickup / 60) : null,
                'vehicle' => $vehicle ? [
                    'id' => $vehicle->id,
                    'picture_url' => $vehicle->picture_url,
                    'license_plate' => $vehicle->license_plate,
                    'brand' => $vehicle->brand,
                    'model' => $vehicle->model,
                    'color' => $vehicle->color,
                ] : null,
            ];
        }

        return response()->json(['success' => true, 'order' => $order, 'driver' => $driver]);
    }
}
