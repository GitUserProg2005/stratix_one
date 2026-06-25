<?php

namespace App\Http\Controllers\N8N;

use App\Http\Controllers\Controller;
use App\Models\Run;
use App\Models\Workflow;

class RunController extends Controller
{
    public function getRuns(int $workflowId)
    {
        Workflow::findOrFail($workflowId);

        $runs = Run::query()
            ->where('workflow_id', $workflowId)
            ->whereNotNull('nodes_runtime')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(fn (Run $run) => [
                'id' => $run->id,
                'name' => 'Запуск '.substr($run->id, 0, 8),
                'startedAt' => $run->created_at->format('d.m.Y, H:i'),
                'totalTime' => $run->getExecutionTime(),
                'status' => collect($run->getAllNodes())->contains(fn ($node) => ! ($node['successfully'] ?? false)) ? 'failed' : 'done',
                'nodes' => collect($run->getAllNodes())->map(fn ($node) => [
                    'title' => $node['name'] ?? '',
                    'time' => $node['execution_time'] ?? 0,
                    'status' => ($node['successfully'] ?? false) ? 'done' : 'failed',
                ])->values()->all(),
            ]);

        return response()->json([
            'result' => 'ok',
            'runs' => $runs,
        ]);
    }
}
