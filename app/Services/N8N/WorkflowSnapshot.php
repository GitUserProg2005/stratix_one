<?php

namespace App\Services\N8N;

use Illuminate\Support\Facades\Cache;

class WorkflowSnapshot
{
    const TTL = 10;

    protected string $runId;

    protected array $snapshot;

    public function __construct(
        protected int $workflowId,
        protected array $graph,
        protected $nodes,
        protected $edges,
        protected array $context = [],
    ) {
        $this->runId = uniqid();

        $this->snapshot = [
            'workflow_id' => $workflowId,
            'graph' => $graph,
            'nodes' => collect($nodes)->map(fn ($node) => is_array($node) ? $node : $node->toArray())->values()->all(),
            'edges' => collect($edges)->map(fn ($edge) => is_array($edge) ? $edge : $edge->toArray())->values()->all(),
            'context' => $context,
        ];
    }

    public function create(): string
    {
        Cache::put("runId:{$this->runId}", $this->snapshot, now()->addMinutes(self::TTL));

        return $this->runId;
    }

    public static function load(string $runId): array
    {
        $snapshot = Cache::get("runId:{$runId}");

        if ($snapshot === null) {
            throw new \RuntimeException("Workflow snapshot not found: {$runId}");
        }

        return $snapshot;
    }
}
