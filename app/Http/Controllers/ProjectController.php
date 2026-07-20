<?php

namespace App\Http\Controllers;

use App\Enums\ProjectRoleName;
use App\Enums\ProjectStatus;
use App\Models\Membership;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProjectController extends Controller
{
    public function index(Request $request): Response
    {
        // 1. Проекты юзера + участники/роли/workflows без N+1
        $projects = Project::query()
            ->whereHas('memberships', function ($q) use ($request) {
                $q->where('user_id', $request->user()->id);
            })
            ->with([
                'memberships.user:id,name,email,avatar',
                'memberships.role:id,name',
                'workflows' => function ($q) {
                    $q->withCount('nodes');
                },
            ])
            ->orderByDesc('created_at')
            ->get()
            ->map(function (Project $project) {
                return [
                    'id' => $project->id,
                    'title' => $project->title,
                    'status' => $project->status,
                    'users' => $project->memberships->map(function (Membership $membership) {
                        $user = $membership->user;

                        return [
                            'id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email,
                            'role' => $membership->role?->name,
                            'is_owner' => $membership->role?->name === ProjectRoleName::Owner->value,
                            'avatar_url' => $user->avatar_url,
                        ];
                    })->values(),
                    'workflows' => $project->workflows->map(function ($workflow) {
                        return [
                            'id' => $workflow->id,
                            'name' => $workflow->name,
                            'nodes_count' => $workflow->nodes_count,
                        ];
                    })->values(),
                ];
            });

        // 2. Отдаём страницу
        return Inertia::render('Project/Index', [
            'projects' => $projects,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        // 1. Валидация
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'member_ids' => 'nullable|array',
            'member_ids.*' => 'integer|exists:users,id',
        ]);

        // 2. Роли из сидера
        $ownerRoleId = Role::query()->where('name', ProjectRoleName::Owner->value)->value('id');
        $memberRoleId = Role::query()->where('name', ProjectRoleName::Member->value)->value('id');

        $project = DB::transaction(function () use ($data, $request, $ownerRoleId, $memberRoleId) {
            // 3. Создаём проект
            $project = Project::query()->create([
                'title' => $data['title'],
                'status' => ProjectStatus::Started,
            ]);

            // 4. Создатель = owner
            Membership::query()->create([
                'project_id' => $project->id,
                'user_id' => $request->user()->id,
                'role_id' => $ownerRoleId,
            ]);

            // 5. Добавляем участников как member
            $memberIds = collect($data['member_ids'] ?? [])
                ->map(fn ($id) => (int) $id)
                ->reject(fn ($id) => $id === $request->user()->id)
                ->unique()
                ->values();

            foreach ($memberIds as $userId) {
                Membership::query()->create([
                    'project_id' => $project->id,
                    'user_id' => $userId,
                    'role_id' => $memberRoleId,
                ]);
            }

            return $project;
        });

        // 6. Отдаём проект
        return response()->json([
            'result' => 'ok',
            'project' => $project->load(['memberships.role', 'memberships.user:id,name,email']),
        ], 201);
    }

    public function update(Request $request, Project $project): JsonResponse
    {
        // 1. Только owner может менять проект
        $isOwner = Membership::query()
            ->where('project_id', $project->id)
            ->where('user_id', $request->user()->id)
            ->whereHas('role', fn ($q) => $q->where('name', ProjectRoleName::Owner->value))
            ->exists();

        if (! $isOwner) {
            return response()->json([
                'result' => 'error',
                'message' => 'Только создатель может изменять проект',
            ], 403);
        }

        // 2. Валидация
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'status' => ['nullable', Rule::enum(ProjectStatus::class)],
            'member_ids' => 'nullable|array',
            'member_ids.*' => 'integer|exists:users,id',
        ]);

        $memberRoleId = Role::query()->where('name', ProjectRoleName::Member->value)->value('id');
        $ownerRoleId = Role::query()->where('name', ProjectRoleName::Owner->value)->value('id');

        DB::transaction(function () use ($data, $project, $memberRoleId, $ownerRoleId) {
            // 3. Обновляем название и статус
            $payload = ['title' => $data['title']];

            if (array_key_exists('status', $data) && $data['status'] !== null) {
                $payload['status'] = $data['status'];
            }

            $project->update($payload);

            // 4. Синхронизируем участников (owner не трогаем)
            $memberIds = collect($data['member_ids'] ?? [])
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            $ownerUserIds = Membership::query()
                ->where('project_id', $project->id)
                ->where('role_id', $ownerRoleId)
                ->pluck('user_id');

            $memberIds = $memberIds->reject(fn ($id) => $ownerUserIds->contains($id))->values();

            // 5. Удаляем member-ов, которых убрали из списка
            Membership::query()
                ->where('project_id', $project->id)
                ->where('role_id', $memberRoleId)
                ->whereNotIn('user_id', $memberIds)
                ->delete();

            // 6. Добавляем новых member-ов
            $existingMemberIds = Membership::query()
                ->where('project_id', $project->id)
                ->where('role_id', $memberRoleId)
                ->pluck('user_id');

            foreach ($memberIds as $userId) {
                if ($existingMemberIds->contains($userId)) {
                    continue;
                }

                Membership::query()->create([
                    'project_id' => $project->id,
                    'user_id' => $userId,
                    'role_id' => $memberRoleId,
                ]);
            }
        });

        // 7. Отдаём обновлённый проект
        return response()->json([
            'result' => 'ok',
            'project' => $project->fresh()->load(['memberships.role', 'memberships.user:id,name,email']),
        ]);
    }

    public function assignOwner(Request $request, Project $project): JsonResponse
    {
        // 1. Только owner может назначать владельца
        $isOwner = Membership::query()
            ->where('project_id', $project->id)
            ->where('user_id', $request->user()->id)
            ->whereHas('role', fn ($q) => $q->where('name', ProjectRoleName::Owner->value))
            ->exists();

        if (! $isOwner) {
            return response()->json([
                'result' => 'error',
                'message' => 'Только создатель может назначать владельца',
            ], 403);
        }

        // 2. Валидация выбранного участника
        $data = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
        ]);

        $ownerRoleId = Role::query()->where('name', ProjectRoleName::Owner->value)->value('id');
        $memberRoleId = Role::query()->where('name', ProjectRoleName::Member->value)->value('id');

        $targetMembership = Membership::query()
            ->where('project_id', $project->id)
            ->where('user_id', $data['user_id'])
            ->first();

        if (! $targetMembership) {
            return response()->json([
                'result' => 'error',
                'message' => 'Выбранный пользователь не является участником проекта',
            ], 422);
        }

        if ((int) $targetMembership->role_id === (int) $ownerRoleId) {
            return response()->json([
                'result' => 'error',
                'message' => 'Этот пользователь уже является владельцем',
            ], 422);
        }

        // 3. Передаём владение: старый owner -> member, выбранный участник -> owner
        DB::transaction(function () use ($project, $data, $ownerRoleId, $memberRoleId) {
            Membership::query()
                ->where('project_id', $project->id)
                ->where('role_id', $ownerRoleId)
                ->update(['role_id' => $memberRoleId]);

            Membership::query()
                ->where('project_id', $project->id)
                ->where('user_id', $data['user_id'])
                ->update(['role_id' => $ownerRoleId]);
        });

        return response()->json([
            'result' => 'ok',
            'project' => $project->fresh()->load(['memberships.role', 'memberships.user:id,name,email']),
        ]);
    }

    public function searchUsers(Request $request): JsonResponse
    {
        // 1. Валидация поиска
        $data = $request->validate([
            'query' => 'required|string|min:2|max:100',
            'inside_project_id' => 'nullable|integer|exists:projects,id',
        ]);

        // 2. Ищем юзеров через Meilisearch
        $builder = User::search($data['query']);

        // 3. Если передан inside_project_id — только участники проекта
        if (! empty($data['inside_project_id'])) {
            $projectId = (int) $data['inside_project_id'];
            $builder->query(fn ($q) => $q->whereHas(
                'projects',
                fn ($q) => $q->where('projects.id', $projectId)
            ));
        } else {
            $builder->query(fn ($q) => $q->where('id', '!=', $request->user()->id));
        }

        // 4. Отдаём список (avatar_url через appends модели)
        $users = $builder->take(10)->get();

        return response()->json($users);
    }

    public function getProjects(Request $request): JsonResponse
    {
        // 1. Проекты, где юзер — участник
        $projects = Project::query()
            ->whereHas('memberships', function ($q) use ($request) {
                $q->where('user_id', $request->user()->id);
            })
            ->orderBy('title')
            ->get(['id', 'title']);

        // 2. Отдаём список
        return response()->json([
            'result' => 'ok',
            'projects' => $projects,
        ]);
    }
}
