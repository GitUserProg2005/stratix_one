<?php

namespace App\Http\Controllers;

use App\Models\Context;
use App\Models\Room;
use Illuminate\Http\Request;

class ContextController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'workflow_id' => ['required', 'integer', 'exists:workflows,id'],
        ]);

        // Загружаем контексты workflow
        $contexts = Context::query()
            ->where('workflow_id', $validated['workflow_id'])
            ->latest()
            ->get();

        return response()->json($contexts);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'body' => ['required', 'string'],
            'workflow_id' => ['required', 'integer', 'exists:workflows,id'],
        ]);

        // Создаём новый контекст
        $context = Context::create([
            'body' => $validated['body'],
            'workflow_id' => $validated['workflow_id'],
        ]);

        return response()->json($context, 201);
    }

    public function update(Request $request, Context $context)
    {
        $validated = $request->validate([
            'body' => ['required', 'string'],
        ]);

        // Обновляем тело контекста
        $context->update(['body' => $validated['body']]);

        return response()->json($context);
    }

    public function destroy(Context $context)
    {
        // Удаляем контекст
        $context->delete();

        return response()->noContent();
    }

    public function updateRoomContext(Request $request, Room $room)
    {
        $validated = $request->validate([
            'context_id' => ['nullable', 'integer', 'exists:contexts,id'],
        ]);

        if (! $request->user() || (int) $room->owner_id !== (int) $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // Привязываем контекст к комнате
        $room->update(['context_id' => $validated['context_id']]);

        return response()->json($room->load('context'));
    }
}
