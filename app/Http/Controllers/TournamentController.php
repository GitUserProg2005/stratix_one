<?php

namespace App\Http\Controllers;

use App\Events\DriverPointsAdded;
use App\Events\DriverPolygonUpdated;
use App\Models\Tournament;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class TournamentController extends Controller
{
    public function active(): JsonResponse
    {
        $tournament = Tournament::query()->latest('id')->first();
        if (!$tournament) {
            return response()->json(['tournament' => null]);
        }

        return response()->json([
            'tournament' => $tournament,
            'participants' => $tournament->participants()->select('users.id', 'users.name')->get(),
            'territories' => $this->territories($tournament->id),
        ]);
    }

    public function join(Request $request, Tournament $tournament): JsonResponse
    {
        $validated = $request->validate([
            'driver_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $driverId = (int) ($validated['driver_id'] ?? $request->user()->id);
        $tournament->participants()->syncWithoutDetaching([$driverId]);

        // MVP reset: start driver tournament run from clean state.
        DB::table('driver_points')
            ->where('tournament_id', $tournament->id)
            ->where('driver_id', $driverId)
            ->delete();
        DB::table('driver_territories')
            ->where('tournament_id', $tournament->id)
            ->where('driver_id', $driverId)
            ->delete();

        return response()->json(['success' => true, 'driver_id' => $driverId]);
    }

    public function updatePosition(Request $request, Tournament $tournament): JsonResponse
    {
        $validated = $request->validate([
            'driver_id' => ['required', 'integer', 'exists:users,id'],
            'lat' => ['required', 'numeric'],
            'lng' => ['required', 'numeric'],
            'order_id' => ['nullable', 'integer'],
        ]);

        $driverId = (int) $validated['driver_id'];
        $lat = (float) $validated['lat'];
        $lng = (float) $validated['lng'];

        $tournament->participants()->syncWithoutDetaching([$driverId]);

        DB::table('driver_points')->insert([
            'tournament_id' => $tournament->id,
            'driver_id' => $driverId,
            'order_id' => $validated['order_id'] ?? null,
            'geom' => DB::raw("ST_SetSRID(ST_MakePoint($lng, $lat), 4326)"),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $pointsCount = (int) DB::table('driver_points')
            ->where('tournament_id', $tournament->id)
            ->where('driver_id', $driverId)
            ->count();

        broadcast(new DriverPointsAdded($driverId, $tournament->id, $lat, $lng, $pointsCount));

        if ($pointsCount >= 4) {
            $row = DB::table('driver_points')
                ->selectRaw('ST_AsGeoJSON(ST_ConvexHull(ST_Collect(geom))) as geojson')
                ->where('driver_id', $driverId)
                ->where('tournament_id', $tournament->id)
                ->first();

            $polygonGeojson = $row?->geojson;
            if ($polygonGeojson) {
                DB::statement(
                    "UPDATE driver_territories AS t
                     SET geom = ST_Multi(
                           ST_CollectionExtract(
                               ST_MakeValid(
                                   ST_Difference(
                                       t.geom,
                                       ST_SetSRID(ST_GeomFromGeoJSON(?), 4326)
                                   )
                               ),
                               3
                           )
                         ),
                         updated_at = NOW()
                     WHERE t.driver_id != ? AND t.tournament_id = ?
                       AND ST_Intersects(t.geom, ST_SetSRID(ST_GeomFromGeoJSON(?), 4326))",
                    [$polygonGeojson, $driverId, $tournament->id, $polygonGeojson]
                );

                DB::statement(
                    "DELETE FROM driver_territories WHERE tournament_id = ? AND (geom IS NULL OR ST_IsEmpty(geom))",
                    [$tournament->id]
                );

                DB::statement(
                    "INSERT INTO driver_territories (tournament_id, driver_id, geom, created_at, updated_at)
                     VALUES (?, ?, ST_Multi(ST_SetSRID(ST_GeomFromGeoJSON(?), 4326)), NOW(), NOW())
                     ON CONFLICT (tournament_id, driver_id)
                     DO UPDATE SET geom = EXCLUDED.geom, updated_at = NOW()",
                    [$tournament->id, $driverId, $polygonGeojson]
                );

                // Broadcast all affected territories so client updates clipped opponents in real time.
                $territories = DB::table('driver_territories')
                    ->selectRaw('driver_id, ST_AsGeoJSON(geom) as geojson')
                    ->where('tournament_id', $tournament->id)
                    ->get();

                foreach ($territories as $territory) {
                    if (!empty($territory->geojson)) {
                        broadcast(new DriverPolygonUpdated((int) $territory->driver_id, $tournament->id, $territory->geojson));
                    }
                }
            }
        }

        return response()->json([
            'success' => true,
            'points_count' => $pointsCount,
            'territories' => $this->territories($tournament->id),
        ]);
    }

    public function state(Tournament $tournament): JsonResponse
    {
        return response()->json([
            'tournament' => $tournament,
            'participants' => $tournament->participants()->select('users.id', 'users.name')->get(),
            'territories' => $this->territories($tournament->id),
        ]);
    }

    private function territories(int $tournamentId): array
    {
        return DB::table('driver_territories')
            ->selectRaw('driver_id, ST_AsGeoJSON(geom) as geojson')
            ->where('tournament_id', $tournamentId)
            ->get()
            ->map(fn ($row) => [
                'driver_id' => (int) $row->driver_id,
                'geojson' => $row->geojson,
            ])
            ->values()
            ->all();
    }
}

