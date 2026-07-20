<?php

namespace App\Http\Controllers;

use App\Enums\NodeType;
use App\Enums\TaskDifficulty;
use App\Enums\TaskStatus;
use App\Jobs\ExecuteWorkflow;
use App\Models\Membership;
use App\Models\Node;
use App\Models\Project;
use App\Models\Task;
use App\Models\Workflow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
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
            ->with(['project:id,title', 'workers:id,name,email,avatar'])
            ->withDepth()
            ->defaultOrder()
            ->get()
            ->toTree();

        // 2. Проекты юзера для формы создания
        $projects = Project::query()
            ->whereHas('memberships', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->with('memberships.user:id,name,email,avatar')
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

            $allowedIds->push((int) $request->user()->id);

            $task->workers()->sync($allowedIds);
        }

        // 7. Отдаём созданную задачу
        return response()->json([
            'result' => 'ok',
            'task' => $task->load(['project:id,title', 'workers:id,name,email,avatar']),
        ], 201);
    }

    public function update(Request $request, Task $task): JsonResponse
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

        // 2. Доступ к задаче (участник текущего проекта)
        $isMember = Membership::query()
            ->where('project_id', $task->project_id)
            ->where('user_id', $request->user()->id)
            ->exists();

        if (! $isMember) {
            return response()->json([
                'result' => 'error',
                'message' => 'Нет доступа к задаче',
            ], 403);
        }

        // 3. Если переносим в другой проект — проверяем доступ и туда
        if ((int) $data['project_id'] !== (int) $task->project_id) {
            $canAccessTarget = Membership::query()
                ->where('project_id', $data['project_id'])
                ->where('user_id', $request->user()->id)
                ->exists();

            if (! $canAccessTarget) {
                return response()->json([
                    'result' => 'error',
                    'message' => 'Нет доступа к проекту',
                ], 403);
            }
        }

        $parent = null;

        if (! empty($data['parent_id'])) {
            // 4. Нельзя быть родителем самому себе
            if ((int) $data['parent_id'] === (int) $task->id) {
                return response()->json([
                    'result' => 'error',
                    'message' => 'Задача не может быть родителем самой себе',
                ], 422);
            }

            // 5. Родитель из того же целевого проекта
            $parent = Task::query()
                ->where('project_id', $data['project_id'])
                ->findOrFail($data['parent_id']);

            // 6. Нельзя выбрать потомка как родителя
            if ($task->descendants()->whereKey($parent->id)->exists()) {
                return response()->json([
                    'result' => 'error',
                    'message' => 'Нельзя выбрать дочернюю задачу как родителя',
                ], 422);
            }

            // 7. Лимит вложенности 3
            if ($parent->ancestors()->count() >= 2) {
                return response()->json([
                    'result' => 'error',
                    'message' => 'Максимальная вложенность — 3 уровня',
                ], 422);
            }
        }

        // 8. Обновляем поля
        $task->fill([
            'title' => $data['title'],
            'project_id' => $data['project_id'],
            'status' => $data['status'] ?? $task->status,
            'difficulty' => $data['difficulty'] ?? $task->difficulty,
            'due_at' => $data['due_at'] ?? null,
        ]);

        // 9. Двигаем в дереве (nested set)
        if ($parent) {
            $task->appendToNode($parent)->save();
        } else {
            $task->saveAsRoot();
        }

        // 10. Синхронизируем исполнителей (только участники проекта)
        $workerIds = collect($data['worker_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $allowedIds = Membership::query()
            ->where('project_id', $data['project_id'])
            ->whereIn('user_id', $workerIds)
            ->pluck('user_id');

        $task->workers()->sync($allowedIds);

        // 11. Отдаём задачу
        return response()->json([
            'result' => 'ok',
            'task' => $task->fresh()->load(['project:id,title', 'workers:id,name,email,avatar']),
        ]);
    }

    public function updateStatus(Request $request, Task $task): JsonResponse
    {
        // 1. Валидация статуса
        $data = $request->validate([
            'status' => ['required', Rule::enum(TaskStatus::class)],
        ]);

        // 2. Юзер должен быть участником проекта задачи
        $isMember = Membership::query()
            ->where('project_id', $task->project_id)
            ->where('user_id', $request->user()->id)
            ->exists();

        if (! $isMember) {
            return response()->json([
                'result' => 'error',
                'message' => 'Нет доступа к задаче',
            ], 403);
        }

        // 3. Обновляем статус
        $task->update([
            'status' => $data['status'],
        ]);

        // 4. Отдаём задачу
        return response()->json([
            'result' => 'ok',
            'task' => $task->fresh()->load(['project:id,title', 'workers:id,name,email,avatar']),
        ]);
    }

    public function finalize(Request $request, Task $task): JsonResponse
    {
        // 1. Причина закрытия (опционально)
        $data = $request->validate([
            'reason' => 'nullable|string|max:5000',
        ]);

        // 2. Юзер должен быть участником проекта
        $isMember = Membership::query()
            ->where('project_id', $task->project_id)
            ->where('user_id', $request->user()->id)
            ->exists();

        if (! $isMember) {
            return response()->json([
                'result' => 'error',
                'message' => 'Нет доступа к задаче',
            ], 403);
        }

        // 3. Финализировать можно только из completed
        if ($task->status !== TaskStatus::Completed) {
            return response()->json([
                'result' => 'error',
                'message' => 'Задача должна быть в статусе completed',
            ], 422);
        }

        // 4. Ставим finalized
        $task->update([
            'status' => TaskStatus::Finalized,
        ]);

        $task->load(['project:id,title', 'workers:id,name,email,avatar']);

        // 5. Payload по схеме task_trigger
        $context = [
            'data' => [
                'status' => TaskStatus::Finalized->value,
                'reason' => $data['reason'] ?? null,
                
                'task' => [
                    'id' => $task->id,
                    'title' => $task->title,
                    'difficulty' => $task->difficulty?->value,
                    'due_at' => $task->due_at?->toIso8601String(),
                ],
            ],
        ];

        // 6. Ищем task_trigger ноды, привязанные к этой задаче
        $triggerNodes = Node::query()
            ->where('type', NodeType::TASK_TRIGGER->value)
            ->where(function ($q) use ($task) {
                $q->where('config->task_id', $task->id)
                    ->orWhere('config->task_id', (string) $task->id);
            })
            ->get(['id', 'workflow_id']);

        // 7. Запускаем каждый связанный workflow с task_trigger
        foreach ($triggerNodes as $node) {
            ExecuteWorkflow::dispatch(
                (int) $node->workflow_id,
                (int) $node->id,
                $context,
            );
        }

        // 8. Отдаём задачу
        return response()->json([
            'result' => 'ok',
            'task' => $task,
            'triggered' => $triggerNodes->count(),
        ]);
    }

    public function delete(Request $request, Task $task): RedirectResponse
    {
        // 1. Юзер должен быть участником проекта задачи
        $isMember = Membership::query()
            ->where('project_id', $task->project_id)
            ->where('user_id', $request->user()->id)
            ->exists();

        if (! $isMember) {
            abort(403, 'Нет доступа к задаче');
        }

        // 2. Удаляем через модель (nested set сам уберёт потомков и поправит дерево)
        $task->delete();

        // 3. Назад на список задач
        return redirect()->route('tasks.index');
    }

    public function getTasks(Request $request, Workflow $workflow): JsonResponse
    {
        // Без проекта у workflow нечего выбирать
        if (! $workflow->project_id) {
            return response()->json([
                'result' => 'ok',
                'tasks' => [],
            ]);
        }

        // Юзер должен быть участником проекта workflow
        $isMember = Membership::query()
            ->where('project_id', $workflow->project_id)
            ->where('user_id', $request->user()->id)
            ->exists();

        if (! $isMember) {
            return response()->json([
                'result' => 'error',
                'message' => 'Нет доступа к проекту',
            ], 403);
        }

        // Задачи только этого проекта
        $tasks = Task::query()
            ->where('project_id', $workflow->project_id)
            ->orderBy('title')
            ->get(['id', 'title'])
            ->map(fn (Task $task) => [
                'id' => $task->id,
                'title' => $task->title,
            ]);

        return response()->json([
            'result' => 'ok',
            'tasks' => $tasks,
        ]);
    }
}
