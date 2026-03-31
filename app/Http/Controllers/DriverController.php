<?php

namespace App\Http\Controllers;

use App\Events\DriverLocationUpdated;
use App\Models\User;
use Illuminate\Http\Request;
use Sk\Geohash\Geohash;

class DriverController extends Controller
{
    public function updatePosition(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'lat' => ['required', 'numeric'],
            'lng' => ['required', 'numeric'],
        ]);

        $user = User::findOrFail($validated['user_id']);
        $user->update([
            'lat' => $validated['lat'],
            'lng' => $validated['lng'],
            'is_online' => true,
        ]);

        $geohash = (new Geohash())->encode((float) $validated['lat'], (float) $validated['lng'], 6);
        event(new DriverLocationUpdated($user->fresh(), $geohash));

        return response()->json(['success' => true]);
    }
}
