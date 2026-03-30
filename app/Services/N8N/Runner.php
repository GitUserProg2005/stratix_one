<?php

namespace App\Services\N8N;

use App\Enums\NodeType;
use App\Events\WorkflowStep;
use App\Services\AI\Gigachat;
use App\Services\N8N\Handles\AiRequest;
use App\Services\N8N\Handles\CollectMetrics;
use App\Services\N8N\Handles\Condition;
use App\Services\N8N\Handles\EmailReport;
use App\Services\N8N\Handles\LogNode;

class Runner
{
    protected int $chunkSize = 1000;

    protected array $graph = [];

    public function __construct(
        protected int $workflowId,
        protected $nodes,
        protected $edges,
        protected array $context = [],
        protected ?Gigachat $gigachat = null,
    ) {
        $this->gigachat = $gigachat ?? app(Gigachat::class);

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

    protected function runNode($nodeId, $previousResult = null, &$nodeResults = [])
    {
        $node = $this->nodes->firstWhere('id', $nodeId);
        $type = NodeType::from($node->type);

        $result = match ($type) {
            NodeType::AI_REQUEST => AiRequest::handleAiRequest($this->gigachat, $node, $previousResult, false),
            NodeType::AI_AGENT_REQUEST => AiRequest::handleAiRequest($this->gigachat, $node, $previousResult, true),
            NodeType::EMAIL_REPORT => EmailReport::handleEmailReport($node, (string) ($previousResult ?? '')),
            NodeType::COLLECT_METRICS => CollectMetrics::handleCollectMetrics($node),
            NodeType::CONDITION => Condition::handleCondition($node, $previousResult),
            NodeType::LOG => LogNode::handle($node, $previousResult),
        };

        $nodeResults[$nodeId] = $result;

        $nextIds = $this->graph[$nodeId] ?? [];
        $nextForBroadcast = $this->firstNextId($nextIds);

        $this->broadcastInChunks((string) $result, $nodeId, $nextForBroadcast);

        $nextNodeIds = $nextIds;

        if ($type === NodeType::CONDITION) {
            $nextNodeIds = [$result];
        }

        foreach ($nextNodeIds ?? [] as $nextNodeId) {
            $this->runNode($nextNodeId, $result, $nodeResults);
        }

        return $result;
    }

    protected function firstNextId(array $ids): ?int
    {
        $first = $ids[0] ?? null;

        return $first !== null ? (int) $first : null;
    }

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
