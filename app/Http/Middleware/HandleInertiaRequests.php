<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        
        return [
            ...parent::share($request),
            'mapServices' => [
                'tileserverUrl' => config('services.map.tileserver_url'),
                'osrmUrl' => config('services.map.osrm_url'),
                'tileserverStyle' => config('services.map.tileserver_style'),
            ],
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'avatar_url' => $user->avatar_url,
                    'role' => $user->role?->value ?? 'passenger',
                    'lat' => $user->lat ? (float) $user->lat : null,
                    'lng' => $user->lng ? (float) $user->lng : null,
                    'is_online' => (bool) $user->is_online,
                    'orders_count' => $user->ordersAsCustomer()->count(),
                ] : null,
            ],
        ];
    }
}
