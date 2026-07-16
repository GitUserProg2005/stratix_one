<?php

namespace App\Services\AI\Actions;

use App\Enums\MessageType;
use App\Enums\NodeType;
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

    public function buildPrompt(): string
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

        // Подгружаем body контекста, привязанного к комнате
        $this->room->loadMissing('context');
        $context = (string) ($this->room->context?->body ?? '');

        return PromptBuilder::build(
            $this->mode,
            $this->userPrompt,
            $nodesJson,
            $edgesJson,
            $nodeTypes,
            $context,
        );
    }

    public function handle(): array
    {
        $jsonFormat = match ($this->mode) {
            MessageType::ASK => false,
            MessageType::AGENT, MessageType::PLAN => true,
        };

        $response = $this->gigachat->sendRequest($this->buildPrompt(), $jsonFormat);

        if ($this->mode === MessageType::ASK) {
            $text = is_string($response) ? $response : (is_scalar($response) ? (string) $response : json_encode($response, JSON_UNESCAPED_UNICODE));

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

        if (! is_array($response)) {
            return [
                'mode' => 'agent',
                'action_type' => 'create',
                'result' => null,
            ];
        }

        $actionType = $response['action_type'] ?? $response['type'] ?? 'create';

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
}
