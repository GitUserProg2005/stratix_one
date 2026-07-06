<?php

namespace App\Services\N8N;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;


class DataTransform {
    protected Collection $edges;

    public function __construct(Collection $edges) {
        $this->edges = $edges;
    }

    /**
     * Трансформация данных между нодами
     * @param int $sourceId стартовая нода
     * @param int $targetId конечная нода
     * @param $data данные для маппинга
     * @return array новая мутировавшая 
     * структура с данными от пред. ноды
     */
    public function applyMapping(int $sourceId, int $targetId, $data): array {
        $edge = $this->edges->first(function ($edge) use ($sourceId, $targetId) {
            $source = is_array($edge) ? $edge['source_node_id'] : $edge->source_node_id;
            $target = is_array($edge) ? $edge['target_node_id'] : $edge->target_node_id;

            return $source == $sourceId && $target == $targetId;
        });

        \Log::info('DATA: '. json_encode($data));

        if (! $edge) {
            return $data;
        }

        $transform = is_array($edge) ? ($edge['transform'] ?? null) : ($edge->transform ?? null);

        if (!$transform || empty($transform['ast'])) {
            return $data;
        }
        
        $payload = $data['data'] ?? $data;
        $mapped = [];

        // Data-Mapping
        $mapped = $this->transformSchema($transform['ast'], $payload);
        
        // foreach ($transform['mappings'] as $target => $source) {
        //     $mapped[$target] = data_get($payload, $source);
        // }

        // LOG ABOUT RESULT
        \Log::info('MAPPED DATA: ' . json_encode($mapped));

        return [
            'data' => $mapped,
            'meta' => $data['meta'] ?? [],
            'error' => $data['error'] ?? null
        ];
    }

    /**
     * Строит фрагмент входных данных целевой ноды по AST ребра.
     *
     * У leaf-узлов type=field есть «from» — путь в данных источника (как в data_get).
     * У group/array «from» опционален: если задан, сужает $data до поддерева перед дочерними полями;
     * если нет — вниз передаётся тот же $data (типичный случай: только у field в AST есть пути).
     *
     * Группа с name оборачивает результат детей в один ключ (вложенный объект), иначе дети сливаются
     * в один ассоциативный массив (корень без имени).
     */
    protected function transformSchema(array $schema, $data) {
        if ($schema['type'] === 'field') {
            $from = $schema['from'] ?? null;

            if ($from && str_contains($from, '[]')) {
                $from = ltrim(Str::after($from, '[]'), '.');
            }

            return [
                $schema['key'] => data_get($data, $from ?? null),
            ];
        }

        if ($schema['type'] === 'group') {
            $source = (isset($schema['from']) && $schema['from'] !== '')
                ? data_get($data, $schema['from'])
                : $data;

            $inner = [];

            foreach ($schema['fields'] ?? [] as $field) {
                $inner = array_merge($inner, $this->transformSchema($field, $source));
            }

            if (! empty($schema['name'])) {
                return [$schema['name'] => $inner];
            }

            return $inner;
        }

        if ($schema['type'] === 'array') {
            $items = (isset($schema['from']) && $schema['from'] !== '')
                ? data_get($data, $schema['from'], [])
                : $data;

            if (! is_array($items)) {
                $items = [];
            }

            return [
                $schema['name'] => collect($items)
                    ->map(fn ($item) => $this->transformSchema($schema['items'], $item))
                    ->toArray(),
            ];
        }

        return [];
    }
}