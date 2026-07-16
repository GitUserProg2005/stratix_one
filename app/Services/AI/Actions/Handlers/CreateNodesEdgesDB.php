<?php

namespace App\Services\AI\Actions\Handlers;

use App\Models\Edge;
use App\Models\Node;
use Illuminate\Support\Facades\DB;

class CreateNodesEdgesDB
{
    public static function handle(int $workflowId, array $workflowData): array
    {
        DB::beginTransaction();

        try {
            $nodesMapId = [];

            // Создаём узлы с безопасным разбором payload
            foreach ($workflowData['nodes'] ?? [] as $index => $payload) {
                if (! isset($payload['id'], $payload['type'])) {
                    continue;
                }

                $nodeModel = Node::create([
                    'workflow_id' => $workflowId,
                    'type' => $payload['type'],
                    'order' => 0,
                    'title' => array_key_exists('title', $payload) ? $payload['title'] : null,
                    'config' => array_key_exists('config', $payload) ? $payload['config'] : null,
                    'position' => array_key_exists('position', $payload) ? $payload['position'] : null,
                ]);

                $nodesMapId[(int) $payload['id']] = $nodeModel->id;
                $workflowData['nodes'][$index]['id'] = $nodeModel->id;
            }

            // Создаём связи с безопасным разбором payload
            foreach ($workflowData['edges'] ?? [] as $index => $payload) {
                if (! isset($payload['source_node_id'], $payload['target_node_id'])) {
                    continue;
                }

                $sourceTemp = (int) $payload['source_node_id'];
                $targetTemp = (int) $payload['target_node_id'];

                if (! isset($nodesMapId[$sourceTemp], $nodesMapId[$targetTemp])) {
                    throw new \RuntimeException(
                        'Edge ссылается на несуществующие временные id узлов: '.$sourceTemp.', '.$targetTemp
                    );
                }

                $sourceDb = $nodesMapId[$sourceTemp];
                $targetDb = $nodesMapId[$targetTemp];

                $edgeModel = Edge::create([
                    'workflow_id' => $workflowId,
                    'source_node_id' => $sourceDb,
                    'target_node_id' => $targetDb,
                    'type' => $payload['type'] ?? 'custom',
                    'label' => array_key_exists('label', $payload) ? $payload['label'] : null,
                    'data' => array_key_exists('data', $payload) ? $payload['data'] : null,
                    'transform' => array_key_exists('transform', $payload) ? $payload['transform'] : null,
                ]);

                $workflowData['edges'][$index]['id'] = $edgeModel->id;
                $workflowData['edges'][$index]['source_node_id'] = $sourceDb;
                $workflowData['edges'][$index]['target_node_id'] = $targetDb;
            }

            $workflowData['action_type'] = 'create';

            DB::commit();

            return [
                'success' => true,
                'result' => 'ok',
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
