<?php

namespace App\Services\Home\Recommendation;

use Illuminate\Support\Collection;


class ParametersVectorBuilder {
    public static function build(
        Collection $items,
        array $parameters,
        callable $weightResolver,
        callable $parameterResolver
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
            
            $mean = $totalWeight > 0 
                ? $weightedSum / $totalWeight : 0;

            // Min-max-scaling
            $values = $items->map(
                fn($item) => $parameterResolver($item, $param)
            )->filter(fn ($v) => $v !== null)->values();

            $vector[$param] = self::minMaxScale($mean, $values);
        }

        return $vector;
    }

    protected static function minMaxScale(
            float $value,
            Collection|array $values, 
        ): float { 
            if ($values->isEmpty()) {
                return 0;
            }

            $min = $values->min();
            $max = $values->max();
            
            if ($max - $min == 0) {
                return 0;
            }

            return ($value - $min) / ($max - $min);
    } 
}