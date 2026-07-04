<?php

namespace App\Http\Controllers\N8N;

use App\Http\Controllers\Controller;
use App\Models\Workflow;
use App\Services\N8N\CheckRate;
use App\Services\N8N\Runner;
use Illuminate\Http\Request;

use Inertia\Inertia;


class WorkflowController extends Controller
{
    public function getWorkflows()
    {
        $workflows = Workflow::all();

        return response()->json([
            'result' => 'ok',
            'workflows' => $workflows,
        ]);
    }

    public function showWorkflow(int $workflowId)
    {
        $workflow = Workflow::with('nodes')->findOrFail($workflowId);

        return Inertia::render('N8N/Workflow', [
            'result' => 'ok',
            'workflow' => $workflow,
        ]);
    }

    public function createWorkflow(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'meta' => 'nullable|array',
        ]);

        $workflow = Workflow::create([
            'name' => $data['name'],
            'meta' => $data['meta'] ?? null,
        ]);

        return response()->json([
            'result' => 'ok',
            'workflow' => $workflow,
        ]);
    }

    public function deleteWorkflow(int $workflowId)
    {
        Workflow::where('id', $workflowId)->delete();

        return response()->json([
            'result' => 'ok',
        ]);
    }

    public function updateWorkflow(Request $request, int $workflowId)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $workflow = Workflow::findOrFail($workflowId);
        $workflow->name = $data['name'];
        $workflow->save();

        return response()->json([
            'result' => 'ok',
            'workflow' => $workflow,
        ]);
    }
 
    public function runWorkflow(Request $request, int $workflowId, CheckRate $checkRate)
    {
        $workflow = Workflow::with(['nodes', 'nodes.edges'])->findOrFail($workflowId);
        $nodes = $workflow->nodes;
        $edges = $workflow->nodes->flatMap(fn ($node) => $node->edges);

        if (!$checkRate->checkRate(auth()->user()->rate_id, $nodes)) {
            return response()->json([
                'result' => 'error',
                'message' => 'У вас нет доступа к нодам этого workflow',
            ], 403);
        }

        $runner = new Runner($workflowId, $nodes, $edges);
        $runner->commitPoints();

        return response()->json([
            'result' => 'ok',
            'context' => 'Workflow finished',
        ]);
    }
}
