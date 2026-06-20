<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;


class WorkflowStep implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $workflowId,
        public string $result,
        public mixed $currentNodeId,
        public ?int $nextProcessingNodeId = null,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('workflow-step.'.$this->workflowId),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'workflowId' => $this->workflowId,
            'result' => $this->result,
            'currentNodeId' => $this->currentNodeId,
            'nextProcessingNodeId' => $this->nextProcessingNodeId,
        ];
    }
}
