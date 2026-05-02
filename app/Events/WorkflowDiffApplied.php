<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WorkflowDiffApplied implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $workflowId,
        public array $payload,
    ) {}

    /**
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('workflow-diff-applied.'.$this->workflowId),
        ];
    }

    /**
     * payload: action_type, nodes, edges, опционально removed_node_ids / removed_edge_ids для delete
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return array_merge(
            ['workflowId' => $this->workflowId],
            $this->payload
        );
    }
}
