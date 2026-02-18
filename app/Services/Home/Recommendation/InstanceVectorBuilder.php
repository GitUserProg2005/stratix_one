<?php

namespace App\Services\Home\Recommendation;


class InstanceVectorBuilder {
    public static function build(
        $instance,
        $allTagIds,
        $parameters,
        callable $tagResolver,
        callable $parameterResolver,
        array $globalStats
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

            $mean = $globalStats[$param]['mean'] ?? 0;
            $std  = $globalStats[$param]['std'] ?? 0;

            $vector[] = $std != 0
                ? ($value - $mean) / $std
                : 0;
        }

        return $vector;
    }
}