<?php

namespace App\Services\N8N;

use App\Enums\NodeType;
use App\Events\WorkflowStep;
use App\Events\WorkflowFailed;


class Runner
{
    protected int $chunkSize = 1000;

    protected array $graph = [];

    protected array $nodeHandlers = [
        NodeType::WEBHOOK_TRIGGER->value => \App\Services\N8N\Handles\WebhookTrigger::class,
        NodeType::AI_REQUEST->value => \App\Services\N8N\Handles\AiRequest::class,
        NodeType::AI_AGENT_REQUEST->value => \App\Services\N8N\Handles\AiAgentRequest::class,
        NodeType::EMAIL_REPORT->value => \App\Services\N8N\Handles\EmailReport::class,
        NodeType::OSRM->value => \App\Services\N8N\Handles\OSRM::class,
        NodeType::COLLECT_METRICS->value => \App\Services\N8N\Handles\CollectMetrics::class,
        NodeType::CONDITION->value => \App\Services\N8N\Handles\Condition::class,
        NodeType::LOG->value => \App\Services\N8N\Handles\LogNode::class,
        NodeType::SCHEDULE->value => \App\Services\N8N\Handles\Schedule::class,
    ];

    public function __construct(
        protected int $workflowId,
        protected $nodes,
        protected $edges,
        protected array $context = [],
    ) {
        foreach ($edges as $edge) {
            $this->graph[$edge->source_node_id][] = $edge->target_node_id;
        }
    }

    public function commitPoints(): string
    {
        $previousResult = null;

        $allTargets = collect($this->edges)->pluck('target_node_id')->unique();
        $startNodes = $this->nodes->whereNotIn('id', $allTargets);

        $nodeResults = [];

        foreach ($startNodes as $node) {
            $result = $this->runNode($node->id, $previousResult, $nodeResults);
            $previousResult = $result;
        }

        return implode("\n", $nodeResults);
    }

    public function startFromNode(int $nodeId): array {
        $nodesResult = [];

        $this->runNode($nodeId, $this->context, $nodesResult);

        return $nodesResult;
    }

    /**
     * Рекурсивное выполнение ноды и всех ее последующих нod в графе    
     * @param int $nodeId ID нodы для выполнения
     * @param mixed $previousResult результат выполнения предыдущей нodы, который может быть нужен
     */
    protected function runNode(int $nodeId, mixed $previousResult = null, &$nodeResults = [])
    {
        // Получаем данные о ноде по ID
        $nodeData = $this->nodes->firstWhere('id', $nodeId);
        $type = NodeType::from($nodeData->type) ?? null;

        // Берем класс по type ноды
        $handlerClass = $this->nodeHandlers[$type->value] ?? null;

        if (!$handlerClass) {
            throw new \Exception("Handler not found for type: {$nodeData->type}");
            $this->error($nodeId);
        }

        // Создаем экземпляр класса и вызываем метод handle
        $handler = new $handlerClass($this->workflowId, $nodeData, $previousResult);
        $result = $handler->handle();

        // if (isset($result['data']['condition_data'])) {
        //     $condition = $result['data']['condition_data'];
// 
        //     $nextNodeId = $condition['next_id'];
// 
        //     $result = [
        //         'data' => $condition['saved_data'],
        //         'meta' => $result['meta'] ?? [],
        //         'error' => $result['error'] ?? null
        //     ];
// 
        //     return [$nextNodeId, $result];
        // }
        
        // Сохраняем результат выполнения ноды
        $nodeResults[$nodeId] = $result;

        // Берем следующие ноды из графа и определяем, какую ноду нужно обработать следующей
        $nextIds = $this->graph[$nodeId] ?? [];
        $nextForBroadcast = $this->firstNextId($nextIds);

        // Бродкастим результат текущей ноды, чтобы фронт мог отображать прогресс выполнения
        $this->broadcastInChunks(
            is_string($result)
                ? $result
                : json_encode($result, JSON_UNESCAPED_UNICODE),
            $nodeId,
            $nextForBroadcast
        );

        $nextNodeIds = $nextIds;

        /**
         * Если нода типа CONDITION, то результатом будет ID следующей ноды, которую нужно выполнить, 
         * а не массив ID, 
         * как для остальных типов нод
        */
        if (
            $type->value === NodeType::CONDITION
            && isset($result['data']['condition_data'])
        ) {
            $condition = $result['data']['condition_data'];

            $nextNodeIds = [$condition['next_id']];

            $result = [
                'data' => $condition['saved_data'],
                'meta' => $result['meta'] ?? [],
                'error' => $result['error'] ?? null
            ];
        }

        // Рекурсивное выполнение последующих нод с передачей результата текущей ноды
        foreach ($nextNodeIds ?? [] as $nextNodeId) {
            $mapped = $this->applyMapping($nodeId, $nextNodeId, $result);

            $this->runNode($nextNodeId, $mapped, $nodeResults);
        }

        return $result;
    }

    /**
     * Трансформация данных между нодами
     * @param int $sourceId стартовая нода
     * @param int $targetId конечная нода
     * @param $data данные для маппинга
     * @return array новая мутировавшая 
     * структура с данными от пред. ноды
     */
    protected function applyMapping(int $sourceId, int $targetId, $data): array {
        $edge = collect($this->edges)->first(function ($edge) use ($sourceId, $targetId) {
            return $edge->source_node_id == $sourceId 
                && $edge->target_node_id == $targetId;
        });

        \Log::info('DATA: '. json_encode($data));

        if (! $edge) {
            return $data;
        }

        $transform = $edge->transform ?? null;

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
            return [
                $schema['key'] => data_get($data, $schema['from'] ?? null),
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

    /**
     * Выборка первого ID следующей нodы для бродкаста, чтобы фронт знал, какой нodе отображать статус "выполняется"
     * @param array $ids массив ID следующих нod, которые нужно выполнить после текущей нodы
     */
    protected function firstNextId(array $ids): ?int
    {
        $first = $ids[0] ?? null;

        return $first !== null ? (int) $first : null;
    }

    protected function error(int $nodeId) {
        broadcast(new WorkflowFailed($this->workflowId, $nodeId));
    }

    /**
     * Бродкаст результата выполнения ноды в чанках, чтобы не перегружать канал большим объемом данных и позволить фронту отображать прогресс выполнения
     * @param string $result результат выполнения ноды, который нужно отправить на фронт
     * @param int $currentNodeId ID текущей ноды, результат которой отправляем на фронт
     * @param int|null $nextNodeId ID следующей ноды,
     */
    protected function broadcastInChunks(string $result, $currentNodeId, ?int $nextNodeId): void
    {
        if (trim($result) === '') {
            broadcast(new WorkflowStep($this->workflowId, '', $currentNodeId, $nextNodeId));

            return;
        }

        $result = mb_convert_encoding($result, 'UTF-8', 'UTF-8');

        $length = mb_strlen($result, 'UTF-8');
        $pos = 0;

        while ($pos < $length) {
            $chunk = mb_substr($result, $pos, $this->chunkSize, 'UTF-8');
            $pos += mb_strlen($chunk, 'UTF-8');

            $chunk = preg_replace('/[\x00-\x1F\x7F]/u', '', $chunk);
            $safeChunkJson = json_encode($chunk, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

            if ($safeChunkJson === false) {
                \Log::error('Ошибка JSON encode чанка', ['error' => json_last_error_msg()]);

                continue;
            }

            $safeChunk = json_decode($safeChunkJson);

            broadcast(new WorkflowStep(
                workflowId: $this->workflowId,
                result: $safeChunk,
                currentNodeId: $currentNodeId,
                nextProcessingNodeId: $nextNodeId
            ));
        }
    }
}
