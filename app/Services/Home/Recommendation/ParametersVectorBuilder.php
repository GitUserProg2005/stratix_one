<?php

namespace App\Services\Home\Recommendation;

use Illuminate\Support\Collection;


class ParametersVectorBuilder {
    public static function build(
        Collection $items,
        array $parameters,
        callable $weightResolver,
        callable $parameterResolver,
        array $globalStats
    ): array {
        $vector = [];

        $totalWeight = $items->sum(fn ($item) => $weightResolver($item));

        foreach ($parameters as $param) {
            // Ср. взвешенное параметра через резолверы
            $weightedSum = $items->sum(function ($item) use (
                $weightResolver,
                $parameterResolver,
                $param
            ) {
              $weight = $weightResolver($item);
              $value = $parameterResolver($item, $param);
              
              return $value * $weight;
            });
            
            $meanValue = $totalWeight > 0 
                ? $weightedSum / $totalWeight : 0;
            
            $globalMean = $globalStats[$param]['mean'] ?? 0;
            $globalStd = $globalStats[$param]['std'] ?? 0;
            
            $vector[$param] = self::zScore(
                $meanValue,
                $globalMean,
                $globalStd
            );
        }

        return $vector;
    }

    protected static function zScore(
        float $value,
        float $mean,
        float $std
    ): float {
        if ($std == 0) {
            return 0;
        }

        return ($value - $mean) / $std;
    }
}