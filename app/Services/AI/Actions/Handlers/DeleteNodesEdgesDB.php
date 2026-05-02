<?php

namespace App\Services\AI\Actions\Handlers;

use Illuminate\Support\Facades\DB;

use App\Models\Node;
use App\Models\Edge;


class DeleteNodesEdgesDB
{
    public static function handle(int $workflowId, array $workflowData)
    {
        DB::beginTransaction();

        try {
            $existingNodes = Node::query()
                ->where('workflow_id', $workflowId)
                ->get()
                ->keyBy('id');

            $existingEdges = Edge::query()
                ->where('workflow_id', $workflowId)
                ->get()
                ->keyBy('id');

            foreach ($workflowData['nodes'] ?? [] as $index => $payload) {
                if (! isset($payload['id'])) {
                    continue;
                }
                
                if ($existingNodes->has($payload['id'])) {
                    $existingNodes->get($payload['id'])->delete();
                }

                if ($existingEdges->has($payload['id'])) {
                    $existingEdges->get($payload['id'])->delete();
                }
            }

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