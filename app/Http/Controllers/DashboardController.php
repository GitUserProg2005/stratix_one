<?php

namespace App\Http\Controllers;

use App\Enums\DashboardWidgetType;
use App\Models\Dashboard;
use App\Models\DashboardWidget;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $dashboards = Dashboard::query()
            ->where('creator_id', auth()->id())
            ->with('workflow')
            ->latest('id')
            ->get()
            ->map(function (Dashboard $d) {
                return [
                    'id' => $d->id,
                    'title' => $d->title,
                    'workflow' => $d->workflow ? ['id' => $d->workflow->id, 'name' => $d->workflow->name] : null,
                    'created_at' => $d->created_at,
                ];
            });

        return Inertia::render('Dashboard/Index', [
            'dashboards' => $dashboards,
        ]);
    }

    public function show(Dashboard $dashboard): Response
    {
        abort_unless($dashboard->creator_id === auth()->id(), 403);

        $dashboard->load('workflow');

        return Inertia::render('Dashboard/Detail', [
            'dashboard' => [
                'id' => $dashboard->id,
                'title' => $dashboard->title,
                'workflow' => $dashboard->workflow ? ['id' => $dashboard->workflow->id, 'name' => $dashboard->workflow->name] : null,
            ],
        ]);
    }

    public function widgets(Dashboard $dashboard): JsonResponse
    {
        abort_unless($dashboard->creator_id === auth()->id(), 403);

        $widgets = DashboardWidget::query()
            ->where('dashboard_id', $dashboard->id)
            ->get();

        return response()->json($widgets);
    }

    public function createDashboard(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'workflow_id' => ['nullable', 'integer', 'exists:workflows,id'],
        ]);

        $dashboard = Dashboard::query()->create([
            'creator_id' => $request->user()->id,
            'workflow_id' => $validated['workflow_id'] ?? null,
            'title' => $validated['title'],
            'layout_config' => null,
        ]);

        return response()->json($dashboard, 201);
    }

    public function createWidget(Request $request, Dashboard $dashboard): JsonResponse
    {
        abort_unless($dashboard->creator_id === auth()->id(), 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'chart_type' => ['required', 'string', 'in:bar,line,pie,doughnut,polarArea,radar'],
            'labels' => ['required', 'array', 'min:1'],
            'labels.*' => ['string', 'max:255'],
            'is_dynamic' => ['sometimes', 'boolean'],
        ]);

        $labels = $validated['labels'];
        $values = array_fill(0, count($labels), 0);

        $content = [
            'labels' => $labels,
            'datasets' => [
                [
                    'title' => $validated['title'],
                    'values' => $values,
                    'is_dynamic' => $validated['is_dynamic'] ?? true,
                    'chart_type' => $validated['chart_type'],
                ],
            ],
        ];

        $widget = DashboardWidget::query()->create([
            'dashboard_id' => $dashboard->id,
            'type' => DashboardWidgetType::CHART,
            'position' => [
                'x' => 0,
                'y' => 0,
                'w' => 2,
                'h' => 2,
            ],
            'content' => $content,
            'metadata' => [],
        ]);

        return response()->json($widget, 201);
    }

    public function updateWidgetPosition(Request $request, DashboardWidget $widget): JsonResponse
    {
        $validated = $request->validate([
            'x' => ['required', 'integer'],
            'y' => ['required', 'integer'],
            'w' => ['required', 'integer'],
            'h' => ['required', 'integer'],
        ]);

        $widget->update([
            'position' => [
                'x' => $validated['x'],
                'y' => $validated['y'],
                'w' => $validated['w'],
                'h' => $validated['h'],
            ],
        ]);

        return response()->json($widget);
    }
}
