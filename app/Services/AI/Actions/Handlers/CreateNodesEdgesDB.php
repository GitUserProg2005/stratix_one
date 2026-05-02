<?php

namespace App\Services\AI\Actions\Handlers;

use Illuminate\Support\Facades\DB;

use App\Models\Node;
use App\Models\Edge;


class CreateNodesEdgesDB {
    public static function handle(int $workflowId, array $workflowData) {
        DB::beginTransaction();

        try {
            $nodes = $workflowData['nodes'];
            $edges = $workflowData['edges'];
            $nodesMapId = [];

            foreach ($nodes as $index => $node) {
                $nodeModel = Node::create([
                    'workflow_id' => $workflowId,
                    'type' => $node['type'],
                    'order' => $node['order'],
                    'title' => $node['title'],
                    'config' => $node['config'],
                    'position' => $node['position'],
                ]);

                $nodesMapId[$node['id']] = $nodeModel->id;

                $workflowData['nodes'][$index]['id'] = $nodeModel->id;
            }

            foreach ($edges as $index => $edge) {
                $edge = Edge::create([
                    'workflow_id' => $workflowId,
                    'source_node_id' => $nodesMapId[$edge['source_node_id']],
                    'target_node_id' => $nodesMapId[$edge['target_node_id']],
                    'type' => $edge['type'] ?? 'custom',
                    'label' => $edge['label'],
                    'data' => $edge['data'],
                    'transform' => $edge['transform'],
                ]);

                $workflowData['edges'][$index]['source_node_id'] = $nodesMapId[$edge['source_node_id']];
                $workflowData['edges'][$index]['target_node_id'] = $nodesMapId[$edge['target_node_id']];
                $workflowData['edges'][$index]['id'] = $edge->id;
            }

            $workflowData['action_type'] = 'create';

            DB::commit();

            return [
                'result' => 'ok',
                'workflowData' => $workflowData,
            ];
        } catch (Throwable $e) {
            DB::rollBack();

            report($e);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}