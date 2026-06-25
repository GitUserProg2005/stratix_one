<?php

namespace App\Services\N8N;

use App\Jobs\RunNode;


class Runner
{
    protected array $graph = [];

    protected string $runId;

    public function __construct(
        protected int $workflowId,
        protected $nodes,
        protected $edges,
        protected array $context = [],
    ) {
        foreach ($edges as $edge) {
            $this->graph[$edge->source_node_id][] = $edge->target_node_id;
        }

        $snapshot = new WorkflowSnapshot(
            workflowId: $this->workflowId,
            graph: $this->graph,
            nodes: $this->nodes,
            edges: $this->edges,
            context: $this->context,
        );

        $snapshot->createRun();
        $this->runId = $snapshot->create();
    }

    public function commitPoints(): string
    {
        $allTargets = collect($this->edges)->pluck('target_node_id')->unique();
        $startNodes = $this->nodes->whereNotIn('id', $allTargets);

        foreach ($startNodes as $node) {
            RunNode::dispatch($this->runId, $node->id);
        }

        return $this->runId;
    }

    public function startFromNode(int $nodeId): string
    {
        RunNode::dispatch($this->runId, $nodeId);

        return $this->runId;
    }
}
