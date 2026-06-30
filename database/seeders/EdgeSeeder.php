<?php

namespace Database\Seeders;

use App\Models\Edge;
use App\Models\Node;
use App\Models\User;
use App\Models\Workflow;
use Database\Seeders\Support\WorkflowSeedData;
use Illuminate\Database\Seeder;

class EdgeSeeder extends Seeder
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

            $nodes = Node::query()
                ->where('workflow_id', $workflow->id)
                ->get()
                ->keyBy(fn (Node $node) => $this->resolveNodeKey($definition['nodes'], $node));

            Edge::query()->where('workflow_id', $workflow->id)->delete();

            foreach ($definition['edges'] as $edgeDef) {
                $source = $nodes->get($edgeDef['source']);
                $target = $nodes->get($edgeDef['target']);

                if (! $source || ! $target) {
                    continue;
                }

                Edge::create([
                    'workflow_id' => $workflow->id,
                    'source_node_id' => $source->id,
                    'target_node_id' => $target->id,
                    'type' => $edgeDef['type'] ?? 'default',
                    'label' => $edgeDef['label'] ?? null,
                    'data' => null,
                    'transform' => null,
                ]);
            }
        }
    }

    /**
     * @param  list<array{key: string, type: string, title: string}>  $nodeDefs
     */
    private function resolveNodeKey(array $nodeDefs, Node $node): ?string
    {
        foreach ($nodeDefs as $nodeDef) {
            if ($nodeDef['type'] === $node->type && $nodeDef['title'] === $node->title) {
                return $nodeDef['key'];
            }
        }

        return null;
    }
}
