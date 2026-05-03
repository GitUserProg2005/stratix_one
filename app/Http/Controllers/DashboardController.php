<?php

namespace App\Http\Controllers;

use App\Models\Dashboard;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Dashboard/Index', [
            'dashboards' => [],
        ]);
    }

    public function widgets(): JsonResponse
    {
        $dashboards = Dashboard::query()
            ->where('creator_id', auth()->id())
            ->with('widgets')
            ->get();

        return response()->json($dashboards);
    }
}
