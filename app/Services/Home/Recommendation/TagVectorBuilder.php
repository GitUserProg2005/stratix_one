<?php

namespace App\Services\Home\Recommendation;

use Illuminate\Support\Collection;


class TagVectorBuilder {
    public static function build(
        Collection $items,
        array $allTagIds,
        callable $tagResolver,
        callable $weightResolver
    ): array {
        $vector = array_fill_keys($allTagIds, 0);

        /**
         * Наполнение вектора с учетом весов и повторений акцентов юзера
        */
        foreach ($items as $item) {
            $weight = $weightResolver($item);
            $tags = $tagResolver($item);

            foreach ($tags as $tag) {
                $vector[$tag->id] += $weight;
            }
        }
        
        $max = max($vector);

        // Нормализация через максимальный акцент юзера
        foreach ($vector as $id => $value) {
            $vector[$id] = $max > 0 ? $value / $max : 0;
        }
        
        return $vector;
    }
}