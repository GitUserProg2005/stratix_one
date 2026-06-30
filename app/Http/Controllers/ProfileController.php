<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Requests\UpdateProfileMediaRequest;
use App\Models\Dashboard;
use App\Models\Edge;
use App\Models\Node;
use App\Models\User;
use App\Models\Workflow;
use App\Services\FileStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function __construct(
        private FileStorageService $fileStorage,
    ) {}

    public function profile(User $user)
    {
        return Inertia::render('Auth/Profile', [
            'user' => $user,
            'stats' => $this->profileStats($user),
        ]);
    }

    /**
     * Страница профиля текущего пользователя.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Auth/Profile', [
            'user' => $request->user(),
            'stats' => $this->profileStats($request->user()),
        ]);
    }

    public function workflowGraph(User $user)
    {
        $clusters = Cache::remember('profile.workflow_graph', now()->addMinutes(15), function () {
            return Workflow::query()
                ->with(['nodes:id,workflow_id,type,title,position', 'edges:id,workflow_id,source_node_id,target_node_id'])
                ->latest('id')
                ->get()
                ->map(fn (Workflow $workflow) => [
                    'workflow_id' => $workflow->id,
                    'workflow_name' => $workflow->name,
                    'nodes' => $workflow->nodes->map(fn (Node $node) => [
                        'id' => $node->id,
                        'title' => $node->title,
                        'type' => $node->type,
                        'position' => $node->position,
                    ])->values()->all(),
                    'edges' => $workflow->edges->map(fn (Edge $edge) => [
                        'id' => $edge->id,
                        'source_node_id' => $edge->source_node_id,
                        'target_node_id' => $edge->target_node_id,
                    ])->values()->all(),
                ])
                ->values()
                ->all();
        });

        return response()->json([
            'result' => 'ok',
            'clusters' => $clusters,
        ]);
    }

    private function profileStats(User $user): array
    {
        return [
            'workflow_count' => Workflow::query()->count(),
            'dashboard_count' => Dashboard::query()
                ->where('creator_id', $user->id)
                ->count(),
        ];
    }

    public function updateMedia(UpdateProfileMediaRequest $request): RedirectResponse
    {
        $user = $request->user();
        $updated = false;

        if ($request->hasFile('avatar')) {
            $this->fileStorage->delete($user->avatar);
            $path = $this->fileStorage->storeUserFile($request->file('avatar'), 'avatars');
            if ($path) {
                $user->avatar = $path;
                $updated = true;
            }
        }

        if ($request->hasFile('background')) {
            $this->fileStorage->delete($user->background);
            $path = $this->fileStorage->storeUserFile($request->file('background'), 'backgrounds');
            if ($path) {
                $user->background = $path;
                $updated = true;
            }
        }

        if ($updated) {
            $user->save();
        }

        return Redirect::route('profile.edit');
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}