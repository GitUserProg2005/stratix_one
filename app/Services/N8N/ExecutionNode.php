<?php

namespace App\Services\N8N;

use App\Enums\NodeType;
use App\Models\Node;
use Illuminate\Support\Collection;

class ExecutionNode
{
    protected array $nodeHandlers = [
        NodeType::WEBHOOK_TRIGGER->value => \App\Services\N8N\Handles\WebhookTrigger::class,
        NodeType::AI_REQUEST->value => \App\Services\N8N\Handles\AiRequest::class,
        NodeType::AI_AGENT_REQUEST->value => \App\Services\N8N\Handles\AiAgentRequest::class,
        NodeType::EMAIL_REPORT->value => \App\Services\N8N\Handles\EmailReport::class,
        NodeType::OSRM->value => \App\Services\N8N\Handles\OSRM::class,
        NodeType::COLLECT_METRICS->value => \App\Services\N8N\Handles\CollectMetrics::class,
        NodeType::UPDATE_METRIC->value => \App\Services\N8N\Handles\UpdateMetric::class,
        NodeType::CONDITION->value => \App\Services\N8N\Handles\Condition::class,
        NodeType::LOG->value => \App\Services\N8N\Handles\LogNode::class,
        NodeType::SCHEDULE->value => \App\Services\N8N\Handles\Schedule::class,
        NodeType::PAGE_LOADER->value => \App\Services\N8N\Handles\PageLoader::class,
    ];

    protected $handler;

    public function __construct(
        protected int $workflowId,
        protected Collection $nodes,
        protected int $nodeId,
        protected mixed $input,
    ) {
        $nodeData = $this->nodes->firstWhere('id', $this->nodeId);

        if ($nodeData === null) {
            throw new \RuntimeException("Node not found: {$this->nodeId}");
        }

        if (is_array($nodeData)) {
            $id = $nodeData['id'] ?? null;
            $nodeData = Node::make($nodeData);

            if ($id !== null) {
                $nodeData->setAttribute('id', $id);
                $nodeData->exists = true;
            }
        }

        $type = NodeType::from($nodeData->type);
        $handlerClass = $this->nodeHandlers[$type->value] ?? null;

        if (! $handlerClass) {
            throw new \RuntimeException("Handler not found for type: {$nodeData->type}");
        }

        $this->handler = new $handlerClass($this->workflowId, $nodeData, $this->input, $this->nodeId);
    }

    public function execute(): mixed
    {
        return $this->handler->handle();
    }
}
