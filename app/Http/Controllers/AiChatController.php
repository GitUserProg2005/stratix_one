<?php

namespace App\Http\Controllers;

use App\Enums\MessageRole;
use App\Enums\MessageType;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

use App\Services\AI\Actions\ActionManager;

use App\Events\WorkflowDiffApplied;

class AiChatController extends Controller
{
    public function getRooms(Request $request)
    {
        $query = Room::query()
            ->with(['context', 'owner'])
            ->withCount('messages')
            ->latest();

        if ($request->user()) {
            $query->where('owner_id', $request->user()->id);
        }

        return response()->json($query->get());
    }

    public function createRoom(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'context_id' => ['nullable', 'integer', 'exists:contexts,id'],
        ]);

        if (! $request->user()) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        $room = Room::create([
            'title' => $validated['title'],
            'owner_id' => $request->user()->id,
            'context_id' => $validated['context_id'] ?? null,
        ]);

        return response()->json($room->load(['context', 'owner']), 201);
    }

    public function deleteRoom(Request $request, Room $room)
    {
        if (! $request->user()) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        if ((int) $room->owner_id !== (int) $request->user()->id) {
            return response()->json([
                'message' => 'Forbidden',
            ], 403);
        }

        $room->delete();

        return response()->noContent();
    }

    public function getMessages(Request $request, Room $room)
    {
        if (! $request->user()) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        if ((int) $room->owner_id !== (int) $request->user()->id) {
            return response()->json([
                'message' => 'Forbidden',
            ], 403);
        }

        $messages = $room->messages()
            ->with('user')
            ->oldest()
            ->get();

        return response()->json($messages);
    }

    public function processMessage(Request $request, Room $room)
    {
        $validated = $request->validate([
            'text' => ['required', 'string', 'max:10000'],
            'type' => ['required', 'string', Rule::in(array_column(MessageType::cases(), 'value'))],
        ]);

        if (! $request->user()) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        if ((int) $room->owner_id !== (int) $request->user()->id) {
            return response()->json([
                'message' => 'Forbidden',
            ], 403);
        }

        $message = $room->messages()->create([
            'text' => $validated['text'],
            'role' => MessageRole::USER->value,
            'type' => $validated['type'],
            'user_id' => $request->user()->id,
            'metadata' => [
                'status' => 'stub',
            ],
        ]);

        $room->loadMissing('context');
        $workflowId = $room->context?->workflow_id;

        if ($workflowId === null) {
            return response()->json([
                'message' => 'Комната без привязанного контекста workflow',
            ], 422);
        }

        $result = app(ActionManager::class, [
            'userPrompt' => $validated['text'],
            'workflowId' => (int) $workflowId,
            'mode' => MessageType::from($validated['type']),
            'room' => $room,
        ])->handle();

        $bundle = $result['result'];
        $aiText = '';
        if (is_array($bundle)) {
            $aiText = (string) ($bundle['workflowData']['answer'] ?? $bundle['error'] ?? '');
        }
        $aiText = $aiText !== '' ? $aiText : 'Готово.';

        $metadata = [
            'status' => 'stub',
            'mode' => $result['mode'] ?? $validated['type'],
            'action_type' => $result['action_type'] ?? null,
        ];
        if (($result['mode'] ?? '') === 'plan' && is_array($bundle) && isset($bundle['workflowData']['todo'])) {
            $metadata['plan_todo'] = $bundle['workflowData']['todo'];
        }

        $aiMessage = $room->messages()->create([
            'text' => $aiText,
            'role' => MessageRole::AI->value,
            'type' => $validated['type'],
            'user_id' => $request->user()->id,
            'metadata' => $metadata,
        ]);

        $ok = $validated['type'] === 'agent'
            && is_array($bundle)
            && isset($bundle['workflowData'])
            && (($bundle['success'] ?? false) === true || ($bundle['result'] ?? null) === 'ok');

        if ($ok) {
            $wf = $bundle['workflowData'];

            broadcast(new WorkflowDiffApplied((int) $workflowId, [
                'action_type' => $result['action_type'] ?? 'create',
                'nodes' => $wf['nodes'] ?? [],
                'edges' => $wf['edges'] ?? [],
                'removed_node_ids' => $wf['node_ids'] ?? [],
                'removed_edge_ids' => $wf['edge_ids'] ?? [],
            ]));
        }

        return response()->json([
            'user_message' => $message->load('user'),
            'ai_message' => $aiMessage->load('user'),
        ], 201);
    }

    public function getContext(Room $room)
    {
        return response()->json(
            $room->context?->load('workflow')
        );
    }
}
