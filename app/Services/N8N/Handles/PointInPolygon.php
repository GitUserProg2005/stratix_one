<?php

namespace App\Services\N8N\Handles;

use App\Enums\NodeStructureSchema;
use App\Services\N8N\BaseNode;
use Illuminate\Support\Facades\DB;

class PointInPolygon extends BaseNode
{
    public static function nodeStructureSchema(): NodeStructureSchema
    {
        return NodeStructureSchema::STATIC;
    }

    public static function inputSchema(): array
    {
        return self::group('coordinates', [
            self::group('point', [
                self::field('x', 'float', true),
                self::field('y', 'float', true),
            ]),
        ]);
    }

    public static function outputSchema(): array
    {
        return self::field('result', 'boolean', true);
    }

    public function handle(): array
    {
        $polygon = $this->getConfig('polygon');

        if (! is_array($polygon) || count($polygon) < 3) {
            return $this->error('В конфиге ноды нужен полигон минимум из 3 точек');
        }

        $lat = (float) $this->input('coordinates.point.x');
        $lon = (float) $this->input('coordinates.point.y');

        if (! is_finite($lat) || ! is_finite($lon)) {
            return $this->error('Некорректные координаты точки');
        }

        try {
            $ring = $this->closeRing($polygon);
            $geojson = json_encode([
                'type' => 'Polygon',
                'coordinates' => [$ring],
            ], JSON_THROW_ON_ERROR);

            $row = DB::selectOne(
                'SELECT ST_Covers(
                    ST_SetSRID(ST_GeomFromGeoJSON(?), 4326),
                    ST_SetSRID(ST_MakePoint(?, ?), 4326)
                ) AS inside',
                [$geojson, $lon, $lat]
            );

            return $this->success([
                'result' => (bool) ($row->inside ?? false),
            ]);
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * @param  array<int, array{0: float|int|string, 1: float|int|string}>  $points
     * @return array<int, array{0: float, 1: float}>
     */
    private function closeRing(array $points): array
    {
        $ring = array_map(function (array $point): array {
            if (count($point) < 2) {
                throw new \InvalidArgumentException('Каждая точка полигона должна быть парой [lng, lat]');
            }

            return [(float) $point[0], (float) $point[1]];
        }, $points);

        $first = $ring[0];
        $last = $ring[count($ring) - 1];

        if ($first[0] !== $last[0] || $first[1] !== $last[1]) {
            $ring[] = $first;
        }

        return $ring;
    }
}
