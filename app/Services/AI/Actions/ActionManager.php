<?php

namespace App\Services\AI\Actions;

use App\Enums\MessageType;
use App\Enums\NodeType;
use App\Events\StatusProcessingUpdated;
use App\Models\Edge;
use App\Models\Node;
use App\Models\Room;
use App\Services\AI\Actions\Handlers\CreateNodesEdgesDB;
use App\Services\AI\Actions\Handlers\DeleteNodesEdgesDB;
use App\Services\AI\Actions\Handlers\UpdateNodesEdgesDB;
use App\Services\AI\Gigachat;

class ActionManager
{
    public function __construct(
        public string $userPrompt,
        public int $workflowId,
        public MessageType $mode,
        protected Gigachat $gigachat,
        public Room $room,
    ) {}

    public function buildPrompt(string $revisions = ''): string
    {
        [$nodesJson, $edgesJson, $nodeTypes] = $this->graphPromptParts();

        // Подгружаем body контекста, привязанного к комнате
        $this->room->loadMissing('context');
        $context = (string) ($this->room->context?->body ?? '');

        // Билдим промпт
        return PromptBuilder::build(
            $this->mode,
            $this->userPrompt,
            $nodesJson,
            $edgesJson,
            $nodeTypes,
            $this->room,
            $context,
            $revisions,
        );
    }

    public function handle(): array
    {
        $jsonFormat = match ($this->mode) {
            MessageType::ASK => false,
            MessageType::AGENT,
            MessageType::PLAN => true,
        };

        if ($this->mode === MessageType::ASK) {
            $response = $this->gigachat->sendRequest($this->buildPrompt(), $jsonFormat);
            $text = is_string($response) ? $response : (is_scalar($response) ? (string) $response : json_encode($response, JSON_UNESCAPED_UNICODE));

            // TEMP: лог ответа AI
            \Log::info('AI ASK response', ['text' => $text]);

            return [
                'mode' => 'ask',
                'action_type' => 'ask',
                'result' => [
                    'success' => true,
                    'workflowData' => [
                        'answer' => trim($text) !== '' ? $text : 'Нет ответа.',
                    ],
                ],
            ];
        }

        if ($this->mode === MessageType::PLAN) {
            $response = $this->gigachat->sendRequest($this->buildPrompt(), $jsonFormat);

            // TEMP: лог ответа AI
            \Log::info('AI PLAN response', ['payload' => $response]);

            if (! is_array($response)) {
                return [
                    'mode' => 'plan',
                    'action_type' => 'plan',
                    'result' => [
                        'success' => false,
                        'error' => 'Неверный формат ответа модели',
                    ],
                ];
            }

            $todo = $response['todo'] ?? [];
            if (! is_array($todo)) {
                $todo = [];
            }

            return [
                'mode' => 'plan',
                'action_type' => 'plan',
                'result' => [
                    'success' => true,
                    'workflowData' => [
                        'answer' => $response['answer'] ?? 'Составляю план по реализации...',
                        'todo' => $todo,
                        'nodes' => [],
                        'edges' => [],
                    ],
                ],
            ];
        }

        // AGENT: генерация → lint/судья → при необходимости retry
        return $this->handleAgent();
    }

    private function handleAgent(): array
    {
        // Обработка запроса пользователя
        $this->broadcastThoughts('Обрабатываю запрос...');

        // Шаг 1: строим скелет workflow
        $response = $this->runAgent();

        if ($response === null) {
            return [
                'mode' => 'agent',
                'action_type' => 'create',
                'result' => null,
            ];
        }

        // Инициализация графа
        $this->broadcastThoughts('Инициализирую граф...');

        // Проверка линтера
        $this->broadcastThoughts('Проверяю ответ линтером...');

        // PHP-lint delete id до судьи (ловим выдуманные edge_ids)
        $phpRevision = $this->lintDeleteResponse($response);
        if ($phpRevision !== null) {
            \Log::info('AI AGENT php lint revisions', ['revisions' => $phpRevision]);
            $retry = $this->runAgent($phpRevision);
            if ($retry !== null) {
                $response = $retry;
            }
        }

        // Промежуточный контроль на стороне судьи
        $this->broadcastThoughts('Судья проверяет решение...');

        // Шаг 2: судья (с актуальным графом)
        [$nodesJson, $edgesJson] = $this->graphPromptParts();
        $verdict = (new JudgeAgent(
            $this->gigachat,
            $this->userPrompt,
            $response,
            $nodesJson,
            $edgesJson,
        ))->handle();

        // TEMP: лог вердикта судьи
        \Log::info('AI JUDGE verdict', ['verdict' => $verdict]);

        // Retry один раз по списку исправлений судьи
        if (! ($verdict['is_correct'] ?? false)) {
            // Правка ошибок
            $this->broadcastThoughts('Исправляю ошибки по замечаниям судьи...');

            $revisions = (string) ($verdict['revisions'] ?? '');

            if ($revisions !== '') {
                \Log::info('AI AGENT retry with revisions', ['revisions' => $revisions]);

                $retry = $this->runAgent($revisions);

                if ($retry !== null) {
                    $response = $retry;
                }
            }
        }

        // Финальный PHP-lint: невалидные id выкидываем / блокируем пустой delete
        $finalLint = $this->lintDeleteResponse($response);
        if ($finalLint !== null) {
            \Log::warning('AI AGENT delete lint failed after retry', ['revisions' => $finalLint, 'payload' => $response]);
        }

        $actionType = $response['action_type'] ?? $response['type'] ?? 'create';

        // Сохранение графа
        $saveThought = match ($actionType) {
            'create' => 'Сохраняю новые узлы и связи...',
            'update' => 'Обновляю узлы и связи...',
            'delete' => 'Удаляю узлы и связи...',
            default => 'Сохраняю изменения графа...',
        };
        $this->broadcastThoughts($saveThought);

        // Проверка доступа к типам нод по тарифу владельца комнаты
        $this->room->loadMissing('owner');
        $owner = $this->room->owner;
        if (
            ! $owner
            || ! (new CheckAccessRate($response['nodes'] ?? [], $owner))->handle()
        ) {
            $msg = 'Вы решили воспользоваться нодой, которая не подлежит вашему текущему тарифу.';
            $this->broadcastThoughts($msg, true);

            return [
                'mode' => 'agent',
                'action_type' => $actionType,
                'result' => [
                    'success' => false,
                    'error' => $msg,
                    'workflowData' => [
                        'answer' => $msg,
                    ],
                ],
            ];
        }

        // Пишем в БД только после генерации (+ retry)
        $result = match ($actionType) {
            'create' => CreateNodesEdgesDB::handle($this->workflowId, $response),
            'update' => UpdateNodesEdgesDB::handle($this->workflowId, $response),
            'delete' => DeleteNodesEdgesDB::handle($this->workflowId, $response),
            default => null,
        };

        \Log::info('ACTION MANAGER RESULT: '.json_encode($result, JSON_UNESCAPED_UNICODE));

        return [
            'mode' => 'agent',
            'action_type' => $actionType,
            'result' => $result,
        ];
    }

    private function broadcastThoughts(string $thoughts, bool $isBroken = false): void
    {
        broadcast(new StatusProcessingUpdated((int) $this->room->id, $thoughts, $isBroken));
    }

    /** Вызов AGENT-режима; $revisions — нумерованный список от судьи для retry. */
    private function runAgent(string $revisions = ''): ?array
    {
        $response = $this->gigachat->sendRequest(
            $this->buildPrompt($revisions),
            true,
        );

        // TEMP: лог ответа AI agent
        \Log::info('AI AGENT response', [
            'has_revisions' => $revisions !== '',
            'payload' => $response,
        ]);

        return is_array($response) ? $response : null;
    }

    /** @return array{0: string, 1: string, 2?: string} */
    private function graphPromptParts(): array
    {
        $nodeTypes = implode(', ', array_column(NodeType::cases(), 'value'));

        $nodesJson = json_encode(
            Node::query()
                ->where('workflow_id', $this->workflowId)
                ->orderBy('id')
                ->get()
                ->map(fn (Node $n) => [
                    'id' => $n->id,
                    'workflow_id' => $n->workflow_id,
                    'type' => $n->type,
                    'title' => $n->title,
                    'config' => $n->config,
                    'position' => $n->position,
                ]),
            JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
        );

        $edgesJson = json_encode(
            Edge::query()
                ->where('workflow_id', $this->workflowId)
                ->get()
                ->map(fn (Edge $e) => [
                    'id' => $e->id,
                    'workflow_id' => $e->workflow_id,
                    'source_node_id' => $e->source_node_id,
                    'target_node_id' => $e->target_node_id,
                    'type' => $e->type,
                    'label' => $e->label,
                    'data' => $e->data,
                    'transform' => $e->transform,
                ]),
            JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
        );

        return [$nodesJson, $edgesJson, $nodeTypes];
    }

    /** Проверка delete: id должны существовать в текущем workflow. */
    private function lintDeleteResponse(array $response): ?string
    {
        $action = $response['action_type'] ?? $response['type'] ?? '';
        if ($action !== 'delete') {
            return null;
        }

        $edgeIds = [];
        foreach ($response['edge_ids'] ?? [] as $id) {
            $edgeIds[] = (int) $id;
        }
        foreach ($response['edges'] ?? [] as $payload) {
            if (isset($payload['id'])) {
                $edgeIds[] = (int) $payload['id'];
            }
        }
        $edgeIds = array_values(array_unique(array_filter($edgeIds)));

        $nodeIds = [];
        foreach ($response['node_ids'] ?? [] as $id) {
            $nodeIds[] = (int) $id;
        }
        foreach ($response['nodes'] ?? [] as $payload) {
            if (isset($payload['id'])) {
                $nodeIds[] = (int) $payload['id'];
            }
        }
        $nodeIds = array_values(array_unique(array_filter($nodeIds)));

        $edges = Edge::query()
            ->where('workflow_id', $this->workflowId)
            ->get(['id', 'source_node_id', 'target_node_id']);

        $edgeCatalog = $edges
            ->map(fn (Edge $e) => "id={$e->id} ({$e->source_node_id}->{$e->target_node_id})")
            ->implode(', ');

        if ($edgeIds === [] && $nodeIds === []) {
            return "1. Для delete укажи edge_ids и/или node_ids. Текущие связи: {$edgeCatalog}. Если нужно удалить несколько связей — перечисли ВСЕ их id в edge_ids.";
        }

        $existingEdgeIds = $edges->pluck('id')->map(fn ($id) => (int) $id)->all();
        $invalidEdges = array_values(array_diff($edgeIds, $existingEdgeIds));

        if ($invalidEdges !== []) {
            return '1. edge_ids '.json_encode($invalidEdges, JSON_UNESCAPED_UNICODE)
                .' не существуют в workflow. Бери id ТОЛЬКО из списка: '
                .$edgeCatalog
                .'. Если пользователь просил удалить несколько связей — в edge_ids должны быть все нужные id сразу.';
        }

        return null;
    }
}
