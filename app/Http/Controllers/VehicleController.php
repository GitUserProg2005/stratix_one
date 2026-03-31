<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    public function getVehicles(Request $request)
    {
        $validated = $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
        ]);

        $lat = (float) $validated['lat'];
        $lng = (float) $validated['lng'];
        $delta = 5 / 111.32;

        $vehicles = Vehicle::with('driver')
            ->whereHas('driver', function ($q) use ($lat, $lng, $delta) {
                $q->whereBetween('lat', [$lat - $delta, $lat + $delta])
                    ->whereBetween('lng', [$lng - $delta, $lng + $delta]);
            })
            ->get()
            ->map(function (Vehicle $v) {
                $driver = $v->driver;
                return [
                    'id' => $v->id,
                    'driver_id' => $driver ? $driver->id : null,
                    'lat' => $driver && $driver->lat !== null ? (float) $driver->lat : null,
                    'lng' => $driver && $driver->lng !== null ? (float) $driver->lng : null,
                    'picture_url' => $v->picture_url,
                    'driver' => $driver ? [
                        'name' => $driver->name,
                        'phone' => $driver->phone,
                    ] : null,
                ];
            })
            ->filter(fn ($item) => $item['lat'] !== null && $item['lng'] !== null && $item['driver_id'] !== null)
            ->values()
            ->all();

        return response()->json(['success' => true, 'vehicles' => $vehicles]);
    }
}
