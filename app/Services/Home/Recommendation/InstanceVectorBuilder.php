<?php

namespace App\Services\Home\Recommendation;


class InstanceVectorBuilder {
    public static function build(
        $instance,
        $allTagIds,
        $parameters,
        callable $tagResolver,
        callable $parameterResolver,
        array $globalMinMax
    ): array {
        $vector = [];
        
        // Дискретные данные
        $instanceTags = $tagResolver($instance)
            ->pluck('id')->toArray();
        
        foreach ($allTagIds as $tagId) {
            $vector[] = in_array($tagId, $instanceTags) ? 1 : 0;
        }

        // Непрерывные данные
        foreach ($parameters as $param) {
            $value = $parameterResolver($instance, $param);

            $min = $globalMinMax[$param]['min'];
            $max = $globalMinMax[$param]['max'];

            $vector[] = ($max - $min) != 0
                ? ($value - $min) / ($max - $min)
                : 0;
        }

        return $vector;
    }
}