<?php

namespace App\Events;

use App\Models\Run;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WorkflowCompleted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Run $run) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('workflow-completed.'.$this->run->workflow_id),
        ];
    }

    public function broadcastWith(): array
    {
        $nodes = collect($this->run->getAllNodes());

        return [
            'workflowId' => $this->run->workflow_id,
            'run' => [
                'id' => $this->run->id,
                'name' => 'Запуск '.substr($this->run->id, 0, 8),
                'startedAt' => $this->run->created_at->format('d.m.Y, H:i'),
                'totalTime' => $this->run->getExecutionTime(),
                'status' => $nodes->contains(fn ($node) => ! ($node['successfully'] ?? false)) ? 'failed' : 'done',
                'nodes' => $nodes->map(fn ($node) => [
                    'title' => $node['name'] ?? '',
                    'time' => $node['execution_time'] ?? 0,
                    'status' => ($node['successfully'] ?? false) ? 'done' : 'failed',
                ])->values()->all(),
            ],
        ];
    }
}
