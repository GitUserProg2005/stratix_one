<?php

namespace App\Services\N8N\Handles;

use App\Enums\NodeStructureSchema;
use App\Services\N8N\BaseNode;

use Illuminate\Support\Facades\Http;


class OSRM extends BaseNode 
{
    public static function nodeStructureSchema(): NodeStructureSchema {
        return NodeStructureSchema::STATIC;
    }

    public static function inputSchema(): array {
        return self::group('coordinates', [
            self::group('pointA', [
                self::field('x', 'float', true),
                self::field('y', 'float', true),
            ]),
            self::group('pointB', [
                self::field('x', 'float', true),
                self::field('y', 'float', true),
            ]),
        ]);
    }

    public static function outputSchema(): array {
        return self::group('final', [
            self::field('path', 'array', true),
            self::field('distance', 'float', false),
            self::field('duration', 'float', false),
        ]);
    }

    public function handle(): array {
        // OSRM в пути ждёт пары {longitude},{latitude} (WGS84).
        // Во входящих данных и вебхуках часто x = широта, y = долгота (как у Москвы: ~55, ~37).
        // Нельзя подставлять x,y как lon,lat — иначе точки окажутся не там, snap даст чужие waypoints и distance≈0.
        $latA = (float) $this->input('coordinates.pointA.x');
        $lonA = (float) $this->input('coordinates.pointA.y');
        $latB = (float) $this->input('coordinates.pointB.x');
        $lonB = (float) $this->input('coordinates.pointB.y');

        \Log::info('OSRM INPUT DATA: ', [
            'lonA' => $lonA,
            'latA' => $latA,
            'lonB' => $lonB,
            'latB' => $latB,
        ]);

        try {
            $baseUrl = config('services.osrm.url', 'http://localhost:5000');

            $url = sprintf(
                '%s/route/v1/driving/%s,%s;%s,%s?overview=full&geometries=geojson',
                rtrim($baseUrl, '/'),
                $lonA,
                $latA,
                $lonB,
                $latB
            );

            $response = Http::timeout(10)->get($url);

            \Log::info('OSRM RESPONSE: '. $response);

            if (!$response->successful()) {
                return $this->error('OSRM request failed');
            }

            $data = $response->json();

            if (($data['code'] ?? null) !== 'Ok') {
                return $this->error('OSRM returned invalid response');
            }

            if (empty($data['routes'][0]['geometry']['coordinates'])) {
                return $this->error('Route not found');
            }

            $coordinates = $data['routes'][0]['geometry']['coordinates'];

            $path = array_map(function ($point) {
                return [
                    $point[0],
                    $point[1]
                ];
            }, $coordinates);

            return $this->success([
                'final' => [
                    'path' => $path,
                    'distance' => $data['routes'][0]['distance'] ?? 0,
                    'duration' => $data['routes'][0]['duration'] ?? 0,
                ]
            ]);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }
}