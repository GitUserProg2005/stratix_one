<?php

namespace App\Http\Controllers\N8N;

use App\Http\Controllers\Controller;
use App\Models\Membership;
use App\Models\Workflow;
use App\Services\N8N\CheckRate;
use App\Services\N8N\Runner;
use Illuminate\Http\Request;

use Inertia\Inertia;


class WorkflowController extends Controller
{
    public function getWorkflows()
    {
        $workflows = Workflow::with('project:id,title')
            ->whereHas('project.memberships', function ($query) {
                $query->where('user_id', auth()->user()->id);
            })
            ->with('project:id,title')
            ->get();

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
        // 1. Валидация
        $data = $request->validate([
            'name' => 'required|string',
            'meta' => 'nullable|array',
            'project_id' => 'nullable|exists:projects,id',
        ]);

        // 2. Если указан проект — проверяем членство
        if (! empty($data['project_id'])) {
            $isMember = Membership::query()
                ->where('project_id', $data['project_id'])
                ->where('user_id', $request->user()->id)
                ->exists();

            if (! $isMember) {
                return response()->json([
                    'result' => 'error',
                    'message' => 'Нет доступа к проекту',
                ], 403);
            }
        }

        // 3. Создаём workflow
        $workflow = Workflow::create([
            'name' => $data['name'],
            'meta' => $data['meta'] ?? null,
            'project_id' => $data['project_id'] ?? null,
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
