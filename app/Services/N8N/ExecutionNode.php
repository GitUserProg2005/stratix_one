<?php

namespace App\Services\N8N;

use App\Enums\NodeType;
use App\Models\Node;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Redis;


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
        NodeType::GO_WHISPER->value => \App\Services\N8N\Handles\GoWhisper::class,
        NodeType::MISTRAL_TEXT->value => \App\Services\N8N\Handles\MistralText::class,
        NodeType::MISTRAL_PICTURE->value => \App\Services\N8N\Handles\MistralPicture::class,
        NodeType::MISTRAL_OCR->value => \App\Services\N8N\Handles\MistralOcr::class,
        NodeType::POINT_IN_POLYGON->value => \App\Services\N8N\Handles\PointInPolygon::class,
    ];

    protected $handler;

    protected string $nodeName;

    public function __construct(
        protected int $workflowId,
        protected string $runId,
        protected Collection $nodes,
        protected int $nodeId,
        protected mixed $input,
    ) {
        $nodeData = $this->nodes->firstWhere('id', $this->nodeId);

        if ($nodeData === null) {
            throw new \RuntimeException("Node not found: {$this->nodeId}");
        }

        $this->nodeName = is_array($nodeData) ? ($nodeData['title'] ?? '') : ($nodeData->title ?? '');

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
        $startTime = now();

        try {
            $output = $this->handler->handle();

            $this->setRuntime($startTime->diffInMilliseconds(now()), true);

            return $output;
        } catch (\Throwable $e) {
            $this->setRuntime($startTime->diffInMilliseconds(now()), false, $e->getMessage());

            throw $e;
        }
    }

    private function setRuntime(int $runtimeMs, bool $successfully, ?string $errorMessage = null): void
    {
        $data = [
            'name' => $this->nodeName,
            'execution_time' => $runtimeMs,
            'successfully' => $successfully,
        ];

        if ($errorMessage !== null) {
            $data['error_message'] = $errorMessage;
        }

        Redis::hset("run:{$this->runId}", (string) $this->nodeId, json_encode($data));
    }
}
