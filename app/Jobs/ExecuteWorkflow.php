<?php

namespace App\Jobs;

use App\Models\Workflow;
use App\Services\N8N\Runner;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExecuteWorkflow implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $workflowId,
        public int $nodeId,
    ) {
    }

    public function handle(): void
    {
        $workflow = Workflow::with('nodes', 'nodes.edges')->findOrFail($this->workflowId);
        $edges = $workflow->nodes->flatMap(fn ($node) => $node->edges ?? []);

        $runner = new Runner(
            workflowId: $workflow->id,
            nodes: $workflow->nodes,
            edges: $edges,
            context: []
        );

        $runner->startFromNode($this->nodeId);
    }
}
