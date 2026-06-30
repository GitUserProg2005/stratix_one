<?php

namespace App\Http\Controllers;

use App\Models\CatalogWorkflow;
use App\Models\Edge;
use App\Models\Node;
use App\Models\Workflow;
use App\Models\WorkflowCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class WorkflowCatalogController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'workflow_category_id' => 'nullable|integer|exists:workflow_categories,id',
        ]);

        $catalogWorkflows = CatalogWorkflow::query()
            ->with([
                'author:id,name,avatar',
                'category:id,title,picture',
            ])
            ->when(
                $request->filled('workflow_category_id'),
                fn ($query) => $query->where('category_id', $request->integer('workflow_category_id')),
            )
            ->latest('id')
            ->get()
            ->map(fn (CatalogWorkflow $entry) => $this->formatCatalogEntry($entry));

        return Inertia::render('N8N/Store/Catalog', [
            'catalogWorkflows' => $catalogWorkflows,
            'categories' => WorkflowCategory::orderBy('title')->get(['id', 'title', 'picture']),
            'filters' => [
                'workflow_category_id' => $request->integer('workflow_category_id') ?: null,
            ],
        ]);
    }

    public function detail(CatalogWorkflow $catalogWorkflow)
    {
        $catalogWorkflow->load([
            'author:id,name,avatar',
            'category:id,title,picture',
        ]);

        return Inertia::render('N8N/Store/Detail', [
            'catalogWorkflow' => $this->formatCatalogEntry($catalogWorkflow),
            'additionalWorkflows' => $this->getAdditionalWorkflows($catalogWorkflow),
        ]);
    }

    public function categories()
    {
        return response()->json([
            'result' => 'ok',
            'categories' => WorkflowCategory::orderBy('title')->get(),
        ]);
    }

    public function publishStatus(int $workflowId)
    {
        $entry = CatalogWorkflow::query()
            ->where('workflow_id', $workflowId)
            ->where('author_id', auth()->id())
            ->first();

        if (! $entry) {
            return response()->json([
                'result' => 'ok',
                'published' => false,
                'catalog_workflow' => null,
            ]);
        }

        return response()->json([
            'result' => 'ok',
            'published' => true,
            'catalog_workflow' => [
                'id' => $entry->id,
                'title' => $entry->title,
                'description' => $entry->description,
                'category_id' => $entry->category_id,
                'downloads' => $entry->downloads,
            ],
        ]);
    }

    public function deploy(Request $request)
    {
        $data = $request->validate([
            'workflow_id' => 'required|exists:workflows,id',
            'category_id' => 'required|exists:workflow_categories,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
        ]);

        $workflow = Workflow::with('nodes')->findOrFail($data['workflow_id']);
        $edges = Edge::where('workflow_id', $workflow->id)->get();

        $graph = [
            'nodes' => $workflow->nodes->toArray(),
            'edges' => $edges->toArray(),
        ];

        $catalogWorkflow = CatalogWorkflow::query()
            ->where('workflow_id', $data['workflow_id'])
            ->where('author_id', auth()->id())
            ->first();

        if ($catalogWorkflow) {
            $catalogWorkflow->update([
                'category_id' => $data['category_id'],
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'graph' => $graph,
            ]);
        } else {
            $catalogWorkflow = CatalogWorkflow::create([
                'author_id' => auth()->id(),
                'workflow_id' => $workflow->id,
                'category_id' => $data['category_id'],
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'graph' => $graph,
            ]);
        }

        Cache::forget($this->authorWorkflowsCacheKey(auth()->id()));
        Cache::forget('profile.workflow_graph');

        return response()->json([
            'result' => 'ok',
            'catalogWorkflow' => $catalogWorkflow,
        ]);
    }

    public function install(CatalogWorkflow $catalogWorkflow)
    {
        $newWorkflow = Workflow::create([
            'name' => $catalogWorkflow->title.' (Из каталога)',
        ]);

        $graph = $catalogWorkflow->graph;
        $nodesMap = [];

        DB::transaction(function () use ($graph, $newWorkflow, &$nodesMap) {
            foreach ($graph['nodes'] ?? [] as $jsonNode) {
                $newNode = Node::create([
                    'workflow_id' => $newWorkflow->id,
                    'type' => $jsonNode['type'],
                    'order' => $jsonNode['order'] ?? 0,
                    'title' => $jsonNode['title'] ?? '',
                    'config' => $jsonNode['config'] ?? null,
                    'position' => $jsonNode['position'] ?? null,
                ]);

                $nodesMap[$jsonNode['id']] = $newNode->id;
            }

            foreach ($graph['edges'] ?? [] as $jsonEdge) {
                $sourceOldId = $jsonEdge['source_node_id'];
                $targetOldId = $jsonEdge['target_node_id'];

                if (! isset($nodesMap[$sourceOldId]) || ! isset($nodesMap[$targetOldId])) {
                    continue;
                }

                Edge::create([
                    'workflow_id' => $newWorkflow->id,
                    'source_node_id' => $nodesMap[$sourceOldId],
                    'target_node_id' => $nodesMap[$targetOldId],
                    'label' => $jsonEdge['label'] ?? null,
                    'type' => $jsonEdge['type'] ?? 'default',
                    'data' => $jsonEdge['data'] ?? null,
                    'transform' => $jsonEdge['transform'] ?? null,
                ]);
            }
        });

        $catalogWorkflow->increment('downloads');

        return redirect()
            ->route('show.workflow', $newWorkflow->id)
            ->with('success', 'Шаблон успешно установлен!');
    }

    private function getAdditionalWorkflows(CatalogWorkflow $current): array
    {
        if (! $current->author_id) {
            return [];
        }

        $authorId = $current->author_id;
        $cacheKey = $this->authorWorkflowsCacheKey($authorId);

        $workflows = Cache::remember($cacheKey, now()->addHour(), function () use ($authorId) {
            return CatalogWorkflow::query()
                ->where('author_id', $authorId)
                ->with(['category:id,title,picture'])
                ->latest('id')
                ->get()
                ->map(fn (CatalogWorkflow $entry) => $this->formatCatalogEntry($entry))
                ->values()
                ->all();
        });

        return array_values(array_filter(
            $workflows,
            fn (array $entry) => $entry['id'] !== $current->id,
        ));
    }

    private function authorWorkflowsCacheKey(int $authorId): string
    {
        return "catalog.additional.{$authorId}";
    }

    private function formatCatalogEntry(CatalogWorkflow $entry): array
    {
        return [
            'id' => $entry->id,
            'title' => $entry->title,
            'description' => $entry->description,
            'downloads' => $entry->downloads,
            'category' => $entry->category,
            'author' => $entry->author ? [
                'id' => $entry->author->id,
                'name' => $entry->author->name,
                'avatar_url' => $entry->author->avatar_url,
            ] : null,
            'nodes' => $entry->graph['nodes'] ?? [],
            'edges' => $entry->graph['edges'] ?? [],
        ];
    }
}
