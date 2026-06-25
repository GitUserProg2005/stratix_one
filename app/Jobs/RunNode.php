<?php

namespace App\Jobs;

use App\Enums\NodeType;
use App\Events\WorkflowCompleted;
use App\Models\Run;
use App\Services\N8N\BroadcastChunks;
use App\Services\N8N\DataTransform;
use App\Services\N8N\ExecutionNode;
use App\Services\N8N\WorkflowSnapshot;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;


class RunNode implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $runId,
        public int $nodeId,
        public mixed $input = null,
    ) {}

    public function handle(): void
    {
        $snapshot = WorkflowSnapshot::load($this->runId);

        $workflowId = $snapshot['workflow_id'];
        $nodes = collect($snapshot['nodes']);
        $edges = collect($snapshot['edges']);
        $graph = $snapshot['graph'];

        $input = $this->input ?? $snapshot['context'] ?? [];

        \Log::info('INPUT: ' . json_encode($input));

        $result = (new ExecutionNode($workflowId, $this->runId, $nodes, $this->nodeId, $input))->execute();

        $nodeData = $nodes->firstWhere('id', $this->nodeId);
        $type = NodeType::from(is_array($nodeData) ? $nodeData['type'] : $nodeData->type);

        $nextIds = $graph[$this->nodeId] ?? [];

        if ($this->isGraphEnd($nextIds)) {
            $this->persistRunResults();
        }

        $nextForBroadcast = isset($nextIds[0]) ? (int) $nextIds[0] : null;

        BroadcastChunks::send(
            $workflowId,
            is_string($result) ? $result : json_encode($result, JSON_UNESCAPED_UNICODE),
            $this->nodeId,
            $nextForBroadcast,
        );

        $nextNodeIds = $nextIds;
        $downstreamResult = $result;

        if (
            $type === NodeType::CONDITION
            && is_array($result)
            && isset($result['data']['condition_data'])
        ) {
            $condition = $result['data']['condition_data'];

            $nextNodeIds = [$condition['next_id']];
            $downstreamResult = [
                'data' => $condition['saved_data'],
                'meta' => $result['meta'] ?? [],
                'error' => $result['error'] ?? null,
            ];
        }

        $transformer = new DataTransform($edges);

        foreach ($nextNodeIds as $nextNodeId) {
            $mapped = $transformer->applyMapping($this->nodeId, (int) $nextNodeId, $downstreamResult);

            self::dispatch($this->runId, (int) $nextNodeId, $mapped);
        }
    }

    private function isGraphEnd(array $nextIds): bool
    {
        return $nextIds === [];
    }

    private function persistRunResults(): void
    {
        $hash = Redis::hgetall("run:{$this->runId}");

        if ($hash === []) {
            return;
        }

        unset($hash['_workflow_id']);

        $nodesRuntime = collect($hash)
            ->mapWithKeys(fn (string $json, string $nodeId) => [$nodeId => json_decode($json, true)])
            ->all();

        Run::where('id', $this->runId)->update(['nodes_runtime' => $nodesRuntime]);

        Redis::del("run:{$this->runId}");

        $run = Run::find($this->runId);

        if ($run) {
            broadcast(new WorkflowCompleted($run));
        }
    }
}
