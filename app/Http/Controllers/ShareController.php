<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Snippet;
use App\Services\Chat\CreateMessage;
use Illuminate\Http\Request;

class ShareController extends Controller
{
    /**
     * Поделиться сниппетом в выбранные чаты.
     * Создаёт сообщение с shareable (morph) в каждом чате.
     */
    public function share(Request $request)
    {
        $request->validate([
            'chat_ids' => 'required|array',
            'chat_ids.*' => 'integer|exists:chats,id',
            'snippet_id' => 'required|exists:snippets,id',
        ]);

        $user = auth()->user();
        $snippet = Snippet::findOrFail($request->snippet_id);
        $chatIds = array_unique($request->chat_ids);

        $chats = Chat::whereHas('users', fn ($q) => $q->where('user_id', $user->id))
            ->whereIn('id', $chatIds)
            ->get();

        if ($chats->isEmpty()) {
            return response()->json(['error' => 'Нет доступных чатов'], 422);
        }

        $createMessage = app(CreateMessage::class);
        foreach ($chats as $chat) {
            $createMessage->create($chat, $user->id, '', $snippet->id, get_class($snippet));
        }

        return response()->json([
            'success' => true,
            'shared_count' => $chats->count(),
        ]);
    }
}
