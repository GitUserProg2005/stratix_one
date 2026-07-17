<?php

namespace App\Services\AI\Actions\Handlers;

use App\Models\Edge;
use App\Models\Node;
use Illuminate\Support\Facades\DB;

class DeleteNodesEdgesDB
{
    public static function handle(int $workflowId, array $workflowData): array
    {
        DB::beginTransaction();

        try {
            // Собираем id узлов: node_ids или nodes[].id
            $nodeIds = [];
            foreach ($workflowData['node_ids'] ?? [] as $id) {
                $nodeIds[] = (int) $id;
            }
            foreach ($workflowData['nodes'] ?? [] as $payload) {
                if (isset($payload['id'])) {
                    $nodeIds[] = (int) $payload['id'];
                }
            }
            $nodeIds = array_values(array_unique(array_filter($nodeIds)));

            // Собираем id рёбер: edge_ids или edges[].id
            $edgeIds = [];
            foreach ($workflowData['edge_ids'] ?? [] as $id) {
                $edgeIds[] = (int) $id;
            }
            foreach ($workflowData['edges'] ?? [] as $payload) {
                if (isset($payload['id'])) {
                    $edgeIds[] = (int) $payload['id'];
                }
            }
            $edgeIds = array_values(array_unique(array_filter($edgeIds)));

            // Удаляем рёбра (сначала — чтобы не ловить FK при удалении узлов)
            $deletedEdgeIds = [];
            if ($edgeIds !== []) {
                $deletedEdgeIds = Edge::query()
                    ->where('workflow_id', $workflowId)
                    ->whereIn('id', $edgeIds)
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                if ($deletedEdgeIds !== []) {
                    Edge::query()
                        ->where('workflow_id', $workflowId)
                        ->whereIn('id', $deletedEdgeIds)
                        ->delete();
                }
            }

            // Удаляем узлы
            $deletedNodeIds = [];
            if ($nodeIds !== []) {
                $deletedNodeIds = Node::query()
                    ->where('workflow_id', $workflowId)
                    ->whereIn('id', $nodeIds)
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                if ($deletedNodeIds !== []) {
                    Node::query()
                        ->where('workflow_id', $workflowId)
                        ->whereIn('id', $deletedNodeIds)
                        ->delete();
                }
            }

            // Нормализуем payload для broadcast (только реально удалённые id)
            $workflowData['action_type'] = 'delete';
            $workflowData['node_ids'] = $deletedNodeIds;
            $workflowData['edge_ids'] = $deletedEdgeIds;
            $workflowData['nodes'] = [];
            $workflowData['edges'] = [];

            \Log::info('DELETE NODES/EDGES', [
                'requested_node_ids' => $nodeIds,
                'requested_edge_ids' => $edgeIds,
                'deleted_node_ids' => $deletedNodeIds,
                'deleted_edge_ids' => $deletedEdgeIds,
            ]);

            DB::commit();

            return [
                'success' => true,
                'workflowData' => $workflowData,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
