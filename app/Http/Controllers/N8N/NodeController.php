<?php

namespace App\Http\Controllers\N8N;

use App\Http\Controllers\Controller;
use App\Models\Edge;
use App\Models\Node;
use App\Models\NodeType;
use App\Enums\NodeType as NodeTypeEnum;
use App\Models\Workflow;
use App\Models\WorkflowTimer;
use App\Models\Webhook;
use App\Services\N8N\Nodes\NodeRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Str;


class NodeController extends Controller
{
    public function getNodes(int $workflowId)
    {
        $nodes = Node::where('workflow_id', $workflowId)->get();

        return response()->json([
            'result' => 'ok',
            'nodes' => $nodes,
        ]);
    }

    public function showNode(int $nodeId)
    {
        $node = Node::findOrFail($nodeId);

        return response()->json([
            'result' => 'ok',
            'node' => $node,
        ]);
    }

    public function createNode(Request $request)
    {
        $data = $request->validate([
            'workflow_id' => 'required|exists:workflows,id',
            'type' => 'required|string',
            'order' => 'required|integer',
            'title' => 'required|string',
            'config' => 'nullable|array',
            'position' => 'required|array',
        ]);

        $node = Node::create([
            'workflow_id' => $data['workflow_id'],
            'type' => $data['type'],
            'order' => $data['order'],
            'title' => $data['title'],
            'config' => $data['config'] ?? null,
            'position' => $data['position'],
        ]);

        // app(NodeFactory::class)->handle($node, []);

        /*
        * Если тип создаваемой ноды webhook-trigger - создаем вебхук с токеном для юзера
        */
        if ($node->type === NodeTypeEnum::WEBHOOK_TRIGGER->value) {
            Webhook::create([
                'workflow_id' => $node->workflow_id,
                'node_id' => $node->id,
                'user_id' => auth()->id(),
                'token' => Str::uuid()
            ]);
        }

        $this->syncWorkflowTimers(
            workflowId: (int) $node->workflow_id,
            nodesPayload: $request->input('nodes')
        );

        return response()->json([
            'result' => 'ok',
            'node' => $node,
        ]);
    }

    public function updateNodePosition(Request $request, int $nodeId)
    {
        $data = $request->validate([
            'position' => 'required|array',
        ]);

        $node = Node::findOrFail($nodeId);
        $node->update([
            'position' => $data['position'],
        ]);

        return response()->json(['result' => 'ok']);
    }

    public function updateNode(Request $request, int $nodeId)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|string',
            'config' => 'nullable|array',
        ]);

        $node = Node::findOrFail($nodeId);
        $node->update($data);

        $this->syncWorkflowTimers(
            workflowId: (int) $node->workflow_id,
            nodesPayload: $request->input('nodes')
        );

        return response()->json([
            'result' => 'ok',
            'node' => $node,
        ]);
    }

    public function deleteNode(int $nodeId)
    {
        $node = Node::findOrFail($nodeId);
        $workflowId = (int) $node->workflow_id;

        $node->delete();

        $this->syncWorkflowTimers($workflowId);

        return response()->json([
            'result' => 'ok',
        ]);
    }

    public function getEdges(int $workflowId)
    {
        $edges = Edge::where('workflow_id', $workflowId)->get();

        return response()->json([
            'result' => 'ok',
            'edges' => $edges,
        ]);
    }

    public function createEdge(Request $request)
    {
        $data = $request->validate([
            'workflow_id' => 'required|exists:workflows,id',
            'source_node_id' => 'required|integer|exists:nodes,id',
            'target_node_id' => 'required|integer|exists:nodes,id',
            'label' => 'nullable|string',
            'type' => 'nullable|string',
            'data' => 'nullable|array',
        ]);

        $edge = Edge::create($data);

        return response()->json([
            'result' => 'ok',
            'edge' => $edge,
        ]);
    }

    public function deleteEdge(int $edgeId)
    {
        Edge::findOrFail($edgeId)->delete();

        return response()->json(['result' => 'ok']);
    }

    public function getNodeTypes()
    {
        $nodeTypes = NodeType::all();

        return response()->json([
            'result' => 'ok',
            'nodeTypes' => $nodeTypes,
        ]);
    }

    public function updateEdgeTransform(Request $request, int $edgeId) {
        \Log::info('REQUEST UPDATER EDGE TRANSFORM: '.$request);
        $edge = Edge::findOrFail($edgeId);

        $edge->transform = $request->transform;
        $edge->save();

        return response()->json([
            'result' => 'ok'
        ]);
    }

    public function getNodeSchemas(NodeRegistry $registry) {
        $schemas = $registry->schemas();

        return response()->json([
            'result' => 'ok',
            'schemas' => $schemas,
        ]);
    }

    protected function syncWorkflowTimers(int $workflowId, ?array $nodesPayload = null): void
    {
        WorkflowTimer::where('workflow_id', $workflowId)->delete();

        $nodes = is_array($nodesPayload)
            ? $nodesPayload
            : Node::query()
                ->where('workflow_id', $workflowId)
                ->get(['id', 'type', 'config'])
                ->map(fn (Node $node) => [
                    'id' => $node->id,
                    'type' => $node->type,
                    'data' => $node->config ?? [],
                ])
                ->all();

        foreach ($nodes as $node) {
            $type = data_get($node, 'type');
            $cron = trim((string) data_get($node, 'data.timing', ''));

            if ($type !== 'schedule' || $cron === '') {
                continue;
            }

            WorkflowTimer::create([
                'workflow_id' => $workflowId,
                'node_id' => (string) data_get($node, 'id'),
                'cron' => $cron,
            ]);
        }
    }
}
