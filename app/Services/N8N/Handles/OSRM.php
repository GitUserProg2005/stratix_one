<?php

namespace App\Services\N8N\Handles;

use App\Services\N8N\BaseNode;
use Illuminate\Support\Facades\Http;

class OSRM extends BaseNode
{
    public static function inputSchema(): array
    {
        return self::inputSchemasByMode()['route'];
    }

    // Каталог input-схем по mode
    public static function inputSchemasByMode(): array
    {
        $point = fn () => self::group(null, [
            self::field('x', 'float', true),
            self::field('y', 'float', true),
        ]);

        return [
            'route' => self::group('coordinates', [
                self::group('pointA', [
                    self::field('x', 'float', true),
                    self::field('y', 'float', true),
                ]),
                self::group('pointB', [
                    self::field('x', 'float', true),
                    self::field('y', 'float', true),
                ]),
            ]),
            'trip' => self::group('coordinates', [
                self::array('points', $point()),
            ]),
            'multi' => self::group('coordinates', [
                self::group('target', [
                    self::field('x', 'float', true),
                    self::field('y', 'float', true),
                ]),
                self::array('agents', $point()),
            ]),
        ];
    }

    public function resolveInputSchema(): ?array
    {
        $mode = $this->getConfig('mode', 'route');
        $schemas = static::inputSchemasByMode();

        return $schemas[$mode] ?? $schemas['route'];
    }

    public static function outputSchema(): array
    {
        return self::group('final', [
            self::field('path', 'array', true),
            self::field('distance', 'float', false),
            self::field('duration', 'float', false),
        ]);
    }

    public function handle(): array
    {
        $mode = $this->getConfig('mode', 'route');

        try {
            switch ($mode) {
                case 'trip':
                    return $this->handleTrip();
                case 'multi':
                    return $this->handleMulti();
                default:
                    return $this->handleRoute();
            }
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    // Маршрут от точки A до точки B
    private function handleRoute(): array
    {
        $latA = (float) $this->input('coordinates.pointA.x');
        $lonA = (float) $this->input('coordinates.pointA.y');
        $latB = (float) $this->input('coordinates.pointB.x');
        $lonB = (float) $this->input('coordinates.pointB.y');

        $coords = $this->coordPair($lonA, $latA).';'.$this->coordPair($lonB, $latB);
        $data = $this->requestOsrm('route', $coords);

        return $this->successFromRoute($data['routes'][0] ?? null);
    }

    // Цепочка точек: дом → школа → заправка
    private function handleTrip(): array
    {
        $points = $this->input('coordinates.points', []);

        if (! is_array($points) || count($points) < 2) {
            return $this->error('Нужно минимум 2 точки');
        }

        // Собираем строку координат для OSRM
        $coords = collect($points)
            ->map(fn (array $point) => $this->coordPair(
                (float) ($point['y'] ?? 0),
                (float) ($point['x'] ?? 0),
            ))
            ->implode(';');

        $data = $this->requestOsrm(
            'trip',
            $coords,
            'overview=full&geometries=geojson&source=first&destination=last&roundtrip=false'
        );

        return $this->successFromRoute($data['trips'][0] ?? null);
    }

    // Ближайший агент до цели
    private function handleMulti(): array
    {
        $targetLat = (float) $this->input('coordinates.target.x');
        $targetLon = (float) $this->input('coordinates.target.y');
        $agents = $this->input('coordinates.agents', []);

        if (! is_array($agents) || count($agents) === 0) {
            return $this->error('Нужен массив агентов');
        }

        $bestIndex = null;
        $bestRoute = null;
        $bestDistance = INF;

        foreach ($agents as $index => $agent) {
            $lat = (float) ($agent['x'] ?? 0);
            $lon = (float) ($agent['y'] ?? 0);

            $coords = $this->coordPair($lon, $lat).';'.$this->coordPair($targetLon, $targetLat);

            try {
                $data = $this->requestOsrm('route', $coords);
                $route = $data['routes'][0] ?? null;
                $distance = (float) ($route['distance'] ?? INF);

                if ($route && $distance < $bestDistance) {
                    $bestDistance = $distance;
                    $bestRoute = $route;
                    $bestIndex = $index;
                }
            } catch (\Throwable) {
                continue;
            }
        }

        if ($bestRoute === null) {
            return $this->error('Не удалось построить маршрут');
        }

        $result = $this->successFromRoute($bestRoute);

        if ($result['error']) {
            return $result;
        }

        $result['meta']['nearest_agent_index'] = $bestIndex;

        return $result;
    }

    // Запрос к OSRM API
    private function requestOsrm(string $service, string $coordinates, string $query = 'overview=full&geometries=geojson'): array
    {
        $baseUrl = rtrim(config('services.osrm.url', 'http://localhost:5000'), '/');
        $url = sprintf('%s/%s/v1/driving/%s?%s', $baseUrl, $service, $coordinates, $query);

        $response = Http::timeout(10)->get($url);

        if (! $response->successful()) {
            throw new \RuntimeException('OSRM request failed');
        }

        $data = $response->json();

        if (($data['code'] ?? null) !== 'Ok') {
            throw new \RuntimeException('OSRM returned invalid response');
        }

        return $data;
    }

    // Формируем ответ ноды из route/trip
    private function successFromRoute(?array $route): array
    {
        if (empty($route['geometry']['coordinates'])) {
            return $this->error('Route not found');
        }

        $path = array_map(
            fn (array $point) => [$point[0], $point[1]],
            $route['geometry']['coordinates']
        );

        return $this->success([
            'final' => [
                'path' => $path,
                'distance' => $route['distance'] ?? 0,
                'duration' => $route['duration'] ?? 0,
            ],
        ]);
    }

    // x = широта, y = долгота → OSRM ждёт lon,lat
    private function coordPair(float $lon, float $lat): string
    {
        return "{$lon},{$lat}";
    }
}
