<?php

namespace Database\Seeders;

use App\Models\Node;
use App\Models\User;
use App\Models\Workflow;
use Database\Seeders\Support\WorkflowSeedData;
use Illuminate\Database\Seeder;

class NodeSeeder extends Seeder
{
    public function run(): void
    {
        $usersByEmail = User::query()
            ->whereIn('email', array_keys(WorkflowSeedData::users()))
            ->pluck('id', 'email');

        foreach (WorkflowSeedData::workflows() as $definition) {
            $userId = $usersByEmail[$definition['user_email']] ?? null;

            if (! $userId) {
                continue;
            }

            $workflow = Workflow::query()
                ->where('user_id', $userId)
                ->where('name', $definition['name'])
                ->first();

            if (! $workflow) {
                continue;
            }

            $nodeIdsByKey = [];

            foreach ($definition['nodes'] as $nodeDef) {
                $config = $nodeDef['config'];

                $node = Node::updateOrCreate(
                    [
                        'workflow_id' => $workflow->id,
                        'type' => $nodeDef['type'],
                        'title' => $nodeDef['title'],
                    ],
                    [
                        'order' => $nodeDef['order'],
                        'config' => $config,
                        'position' => $nodeDef['position'],
                    ],
                );

                $nodeIdsByKey[$nodeDef['key']] = $node->id;
            }

            foreach ($definition['nodes'] as $nodeDef) {
                if (! isset($nodeDef['condition_branches'])) {
                    continue;
                }

                $nodeId = $nodeIdsByKey[$nodeDef['key']] ?? null;
                $trueId = $nodeIdsByKey[$nodeDef['condition_branches']['true']] ?? null;
                $falseId = $nodeIdsByKey[$nodeDef['condition_branches']['false']] ?? null;

                if (! $nodeId || ! $trueId || ! $falseId) {
                    continue;
                }

                $config = $nodeDef['config'];
                $config['condition']['on_true'] = ['node_id' => $trueId];
                $config['condition']['on_false'] = ['node_id' => $falseId];

                Node::whereKey($nodeId)->update(['config' => $config]);
            }
        }
    }
}
