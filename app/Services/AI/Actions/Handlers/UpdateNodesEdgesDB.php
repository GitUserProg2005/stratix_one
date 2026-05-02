<?php

namespace App\Services\AI\Actions\Handlers;

use App\Models\Edge;
use App\Models\Node;
use Illuminate\Support\Facades\DB;

class UpdateNodesEdgesDB
{
    public static function handle(int $workflowId, array $workflowData): array
    {
        DB::beginTransaction();

        try {
            $existingNodes = Node::query()
                ->where('workflow_id', $workflowId)
                ->get()
                ->keyBy('id');

            $nodeIdMap = [];
            foreach ($existingNodes as $id => $model) {
                $nodeIdMap[(int) $id] = (int) $model->id;
            }

            foreach ($workflowData['nodes'] ?? [] as $index => $payload) {
                if (! isset($payload['id'])) {
                    continue;
                }

                if ($existingNodes->has($payload['id'])) {
                    $model = $existingNodes->get($payload['id']);
                    $model->update([
                        'title' => array_key_exists('title', $payload) ? $payload['title'] : $model->title,
                        'config' => array_key_exists('config', $payload) ? $payload['config'] : $model->config,
                        'position' => array_key_exists('position', $payload) ? $payload['position'] : $model->position,
                        'order' => array_key_exists('order', $payload) ? $payload['order'] : $model->order,
                        'type' => array_key_exists('type', $payload) ? $payload['type'] : $model->type,
                    ]);
                    $nodeIdMap[(int) $payload['id']] = $model->id;

                    continue;
                }

                $model = Node::create([
                    'workflow_id' => $workflowId,
                    'type' => array_key_exists('type', $payload) ? $payload['type'] : 'log',
                    'order' => array_key_exists('order', $payload) ? $payload['order'] : 0,
                    'title' => array_key_exists('title', $payload) ? $payload['title'] : null,
                    'config' => array_key_exists('config', $payload) ? $payload['config'] : null,
                    'position' => array_key_exists('position', $payload) ? $payload['position'] : null,
                ]);

                $nodeIdMap[(int) $payload['id']] = $model->id;
                $workflowData['nodes'][$index]['id'] = $model->id;
            }

            $existingEdges = Edge::query()
                ->where('workflow_id', $workflowId)
                ->get()
                ->keyBy('id');

            foreach ($workflowData['edges'] ?? [] as $index => $payload) {
                if (
                    ! isset($payload['id'], $payload['source_node_id'], $payload['target_node_id'])
                ) {
                    continue;
                }

                $sourceDb = $nodeIdMap[(int) $payload['source_node_id']] ?? (int) $payload['source_node_id'];
                $targetDb = $nodeIdMap[(int) $payload['target_node_id']] ?? (int) $payload['target_node_id'];

                $missing = [];
                foreach ([$sourceDb, $targetDb] as $nid) {
                    if (! Node::query()->where('workflow_id', $workflowId)->whereKey($nid)->exists()) {
                        $missing[] = $nid;
                    }
                }
                if ($missing !== []) {
                    throw new \RuntimeException(
                        'Edge ссылается на несуществующие узлы workflow: '.implode(', ', $missing)
                        .'. Каждый source_node_id/target_node_id должен быть либо id из «ТЕКУЩИЕ_УЗЛЫ», либо временный id нового узла из того же ответа (массив nodes).'
                    );
                }

                if ($existingEdges->has($payload['id'])) {
                    $edgeModel = $existingEdges->get($payload['id']);
                    $edgeModel->update([
                        'source_node_id' => $sourceDb,
                        'target_node_id' => $targetDb,
                        'type' => $payload['type'] ?? $edgeModel->type,
                        'label' => array_key_exists('label', $payload) ? $payload['label'] : $edgeModel->label,
                        'data' => array_key_exists('data', $payload) ? $payload['data'] : $edgeModel->data,
                        'transform' => array_key_exists('transform', $payload) ? $payload['transform'] : $edgeModel->transform,
                    ]);

                    $workflowData['edges'][$index]['id'] = $edgeModel->id;
                    $workflowData['edges'][$index]['source_node_id'] = $sourceDb;
                    $workflowData['edges'][$index]['target_node_id'] = $targetDb;

                    continue;
                }

                $edgeModel = Edge::create([
                    'workflow_id' => $workflowId,
                    'source_node_id' => $sourceDb,
                    'target_node_id' => $targetDb,
                    'type' => $payload['type'] ?? 'custom',
                    'label' => $payload['label'] ?? null,
                    'data' => $payload['data'] ?? null,
                    'transform' => $payload['transform'] ?? null,
                ]);

                $workflowData['edges'][$index]['id'] = $edgeModel->id;
                $workflowData['edges'][$index]['source_node_id'] = $sourceDb;
                $workflowData['edges'][$index]['target_node_id'] = $targetDb;
            }

            $workflowData['action_type'] = 'update';

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
