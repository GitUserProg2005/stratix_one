<?php

namespace App\Http\Controllers;

use App\Enums\TaskDifficulty;
use App\Enums\TaskStatus;
use App\Models\Membership;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class TaskContoller extends Controller
{
    public function index(Request $request): Response
    {
        $userId = $request->user()->id;

        // 1. Берём задачи из проектов, где юзер — участник
        $tasks = Task::query()
            ->whereHas('project.memberships', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->with(['project:id,title', 'workers:id,name,avatar'])
            ->withDepth()
            ->defaultOrder()
            ->get();

        // 2. Проекты юзера для формы создания
        $projects = Project::query()
            ->whereHas('memberships', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->orderBy('title')
            ->get(['id', 'title']);

        // 3. Отдаём страницу
        return Inertia::render('Task/Index', [
            'tasks' => $tasks,
            'projects' => $projects,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        // 1. Валидация
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'project_id' => 'required|exists:projects,id',
            'parent_id' => 'nullable|exists:tasks,id',
            'status' => ['nullable', Rule::enum(TaskStatus::class)],
            'difficulty' => ['nullable', Rule::enum(TaskDifficulty::class)],
            'due_at' => 'nullable|date',
            'worker_ids' => 'nullable|array',
            'worker_ids.*' => 'integer|exists:users,id',
        ]);

        // 2. Проверяем, что юзер — участник проекта
        $isMember = Membership::query()
            ->where('project_id', $data['project_id'])
            ->where('user_id', $request->user()->id)
            ->exists();

        if (! $isMember) {
            return response()->json([
                'result' => 'error',
                'message' => 'Нет доступа к проекту',
            ], 403);
        }

        $parent = null;

        if (! empty($data['parent_id'])) {
            // 3. Родитель должен быть из того же проекта
            $parent = Task::query()
                ->where('project_id', $data['project_id'])
                ->findOrFail($data['parent_id']);

            // 4. Лимит вложенности 3: у родителя не больше 1 предка (depth 0 или 1)
            if ($parent->ancestors()->count() >= 2) {
                return response()->json([
                    'result' => 'error',
                    'message' => 'Максимальная вложенность — 3 уровня',
                ], 422);
            }
        }

        // 5. Создаём задачу через nested set (второй аргумент = parent)
        $task = Task::create([
            'project_id' => $data['project_id'],
            'title' => $data['title'],
            'status' => $data['status'] ?? TaskStatus::Started->value,
            'difficulty' => $data['difficulty'] ?? TaskDifficulty::Normal->value,
            'due_at' => $data['due_at'] ?? null,
        ], $parent);

        // 6. Вешаем исполнителей (только участники проекта)
        $workerIds = collect($data['worker_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($workerIds->isNotEmpty()) {
            $allowedIds = Membership::query()
                ->where('project_id', $data['project_id'])
                ->whereIn('user_id', $workerIds)
                ->pluck('user_id');

            $task->workers()->sync($allowedIds);
        }

        // 7. Отдаём созданную задачу
        return response()->json([
            'result' => 'ok',
            'task' => $task->load(['project:id,title', 'workers:id,name,avatar']),
        ], 201);
    }
}
