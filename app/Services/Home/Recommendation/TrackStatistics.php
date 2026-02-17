<?php

namespace App\Services\Home\Recommendation;

use App\Models\Track;


class TrackStatistics {
    public static function calculate(
        array $parameters
    ): array {
        $tracks = Track::query()
            ->select('parameters')
            ->get();

        $stats = [];

        foreach ($parameters as $param) {
            $values = $tracks
                ->pluck("parameters.$param")
                ->filter(fn($v) => $v !== null)
                ->values();
            
            if ($values->isEmpty()) {
                $stats[$param] = [
                    'mean' => 0,
                    'std'  => 0,
                ];
                continue;
            }

            $mean = $values->avg();

            $variance = $values
                ->map(fn($v) => pow($v - $mean, 2))
                ->avg();

            $std = sqrt($variance);

            $stats[$param] = [
                'mean' => $mean,
                'std'  => $std,
            ];
        }

        return $stats;
    }
}