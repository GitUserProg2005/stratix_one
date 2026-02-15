<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\Chat;
use App\Models\Message;
use App\Models\Snippet;
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

        $userChatIds = Chat::whereHas('users', fn ($q) => $q->where('user_id', $user->id))
            ->whereIn('id', $chatIds)
            ->pluck('id')
            ->all();

        if (empty($userChatIds)) {
            return response()->json(['error' => 'Нет доступных чатов'], 422);
        }

        $created = [];
        foreach ($userChatIds as $chatId) {
            $message = Message::create([
                'chat_id' => $chatId,
                'user_id' => $user->id,
                'body' => '',
                'shareable_id' => $snippet->id,
                'shareable_type' => get_class($snippet),
            ]);
            $message->load(['user:id,name,avatar', 'shareable']);
            event(new MessageSent($message));
            $created[] = $message;
        }

        return response()->json([
            'success' => true,
            'shared_count' => count($created),
        ]);
    }
}
