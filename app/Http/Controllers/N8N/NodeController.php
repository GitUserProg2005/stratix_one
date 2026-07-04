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
use App\Services\N8N\CheckRate;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

use App\Events\WorkflowUpdated;


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

    public function createNode(Request $request, CheckRate $checkRate)
    {
        // 1. Валидация входных данных
        $data = $request->validate([
            'workflow_id' => 'required|exists:workflows,id',
            'type' => 'required|string',
            'order' => 'required|integer',
            'title' => 'required|string',
            'config' => 'nullable|array',
            'position' => 'required|array',
        ]);

        // 2. Проверка тарифа: платный тип ноды должен быть в тарифе пользователя
        if (!$checkRate->checkRate(auth()->user()->rate_id, [['type' => $data['type']]])) {
            return response()->json([
                'result' => 'error',
                'message' => 'У вас нет доступа к этому типу ноды',
            ], 403);
        }

        // 3. Создание ноды в workflow
        $node = Node::create([
            'workflow_id' => $data['workflow_id'],
            'type' => $data['type'],
            'order' => $data['order'],
            'title' => $data['title'],
            'config' => $data['config'] ?? null,
            'position' => $data['position'],
        ]);

        // 4. Для webhook-trigger создаём токен вебхука владельцу
        if ($node->type === NodeTypeEnum::WEBHOOK_TRIGGER->value) {
            Webhook::create([
                'workflow_id' => $node->workflow_id,
                'node_id' => $node->id,
                'user_id' => auth()->id(),
                'token' => Str::uuid()
            ]);
        }

        // 5. Пересборка cron-таймеров workflow (schedule-ноды)
        $this->syncWorkflowTimers(
            workflowId: (int) $node->workflow_id,
            nodesPayload: $request->input('nodes')
        );

        // 6. Уведомление клиентов (Reverb) о создании ноды
        WorkflowUpdated::dispatch(
            (int) $node->workflow_id,
            auth()->id(),
            'node.created',
            $node->toArray()
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

    public function updateNode(Request $request, int $nodeId, CheckRate $checkRate)
    {
        // 1. Валидация входных данных
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|string',
            'config' => 'nullable|array',
        ]);

        // 2. Проверка тарифа: нельзя сохранить тип, недоступный пользователю
        if (!$checkRate->checkRate(auth()->user()->rate_id, [['type' => $data['type']]])) {
            return response()->json([
                'result' => 'error',
                'message' => 'У вас нет доступа к этому типу ноды',
            ], 403);
        }

        // 3. Обновление ноды
        $node = Node::findOrFail($nodeId);
        $node->update($data);

        // 4. Пересборка cron-таймеров workflow (schedule-ноды)
        $this->syncWorkflowTimers(
            workflowId: (int) $node->workflow_id,
            nodesPayload: $request->input('nodes')
        );

        // 5. Уведомление клиентов (Reverb) об изменении ноды
        WorkflowUpdated::dispatch(
            (int) $node->workflow_id,
            auth()->id(),
            'node.updated',
            $node->toArray()
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

        WorkflowUpdated::dispatch(
            $workflowId,
            auth()->id(),
            'node.deleted',
            $node->toArray()
        );

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

        WorkflowUpdated::dispatch(
            (int) $edge->workflow_id,
            auth()->id(),
            'edge.created',
            $edge->toArray()
        );

        return response()->json([
            'result' => 'ok',
            'edge' => $edge,
        ]);
    }

    public function deleteEdge(int $edgeId)
    {
        $edge = Edge::findOrFail($edgeId);
        $payload = $edge->toArray();
        $workflowId = (int) $edge->workflow_id;

        $edge->delete();

        WorkflowUpdated::dispatch(
            $workflowId,
            auth()->id(),
            'edge.deleted',
            $payload
        );

        return response()->json(['result' => 'ok']);
    }

    public function getNodeTypes()
    {
        $nodeTypes = NodeType::query()
            ->with('rates:id')
            ->get()
            ->map(fn (NodeType $nodeType) => [
                'id' => $nodeType->id,
                'name' => $nodeType->name,
                'type' => $nodeType->type,
                'description' => $nodeType->description,
                'rate_ids' => $nodeType->rates->pluck('id')->values()->all(),
            ]);

        return response()->json([
            'result' => 'ok',
            'nodeTypes' => $nodeTypes,
        ]);
    }

    public function updateEdgeTransform(Request $request, int $edgeId) {
        $edge = Edge::findOrFail($edgeId);

        $edge->transform = $request->transform;
        $edge->save();

        WorkflowUpdated::dispatch(
            (int) $edge->workflow_id,
            auth()->id(),
            'edge.updated',
            $edge->toArray()
        );

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
