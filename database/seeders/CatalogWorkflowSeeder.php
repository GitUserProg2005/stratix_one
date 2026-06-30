<?php

namespace Database\Seeders;

use App\Models\CatalogWorkflow;
use App\Models\Edge;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowCategory;
use Database\Seeders\Support\WorkflowSeedData;
use Illuminate\Database\Seeder;

class CatalogWorkflowSeeder extends Seeder
{
    public function run(): void
    {
        $usersByEmail = User::query()
            ->whereIn('email', array_keys(WorkflowSeedData::users()))
            ->pluck('id', 'email');

        $categoriesByTitle = WorkflowCategory::query()
            ->pluck('id', 'title');

        foreach (WorkflowSeedData::catalogEntries() as $entry) {
            $userId = $usersByEmail[$entry['user_email']] ?? null;
            $categoryId = $categoriesByTitle[$entry['category_title']] ?? null;

            if (! $userId || ! $categoryId) {
                continue;
            }

            $workflow = Workflow::query()
                ->where('user_id', $userId)
                ->where('name', $entry['workflow_name'])
                ->first();

            if (! $workflow) {
                continue;
            }

            $nodes = $workflow->nodes()->orderBy('order')->get();
            $edges = Edge::query()->where('workflow_id', $workflow->id)->get();

            $graph = [
                'nodes' => $nodes->toArray(),
                'edges' => $edges->toArray(),
            ];

            CatalogWorkflow::updateOrCreate(
                [
                    'workflow_id' => $workflow->id,
                    'author_id' => $userId,
                ],
                [
                    'category_id' => $categoryId,
                    'title' => $entry['title'],
                    'description' => $entry['description'],
                    'downloads' => $entry['downloads'],
                    'graph' => $graph,
                ],
            );
        }
    }
}
