<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use App\Services\Chat\CreateMessage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ChatController extends Controller
{
    /**
     * Список чатов текущего пользователя (для выбора «поделиться в чат»).
     */
    public function listChats(): JsonResponse
    {
        $userId = auth()->id();
        $chats = Chat::whereHas('users', fn ($q) => $q->where('user_id', $userId))
            ->with(['users' => fn ($q) => $q->where('user_id', '!=', $userId)->select('users.id', 'users.name', 'users.avatar')])
            ->get();

        $list = $chats->map(function (Chat $chat) {
            $other = $chat->users->first();
            return [
                'id' => $chat->id,
                'other_user' => $other ? [
                    'id' => $other->id,
                    'name' => $other->name,
                    'avatar_url' => $other->avatar_url,
                ] : null,
            ];
        })->filter(fn ($item) => $item['other_user'] !== null)->values();

        return response()->json($list);
    }

    /**
     * Чат с другом. Чат создаётся только при первой отправке сообщения.
     * Если чата ещё нет — показываем пустой экран переписки.
     */
    public function showWithUser(User $user): Response
    {
        $authUser = auth()->user();
        if ($authUser->id === $user->id) {
            abort(403);
        }

        if (!$this->areFriends($authUser->id, $user->id)) {
            abort(403);
        }

        $chat = $this->findChatBetween($authUser->id, $user->id);
        $initialMessages = $chat
            ? $chat->messages()->with(['user:id,name,avatar', 'shareable'])->get()->map(fn (Message $m) => $this->formatMessage($m))
            : [];
        $streak = $chat?->streak ? [
            'active' => $chat->streak->active,
            'days' => $chat->streak->days,
        ] : null;

        return Inertia::render('Chat/Show', [
            'chat' => $chat ? [
                'id' => $chat->id,
            ] : null,
            'otherUser' => [
                'id' => $user->id,
                'name' => $user->name,
                'avatar_url' => $user->avatar_url,
            ],
            'initialMessages' => $initialMessages,
            'streak' => $streak,
        ]);
    }

    /**
     * Отправка сообщения. При первой отправке другу создаётся чат.
     */
    public function storeMessage(Request $request)
    {
        $request->validate([
            'recipient_id' => 'required|exists:users,id',
            'body' => 'required|string|max:5000',
        ]);

        $authUser = auth()->user();
        $recipientId = (int) $request->recipient_id;

        if ($authUser->id === $recipientId) {
            return response()->json(['error' => 'Invalid recipient'], 422);
        }

        if (!$this->areFriends($authUser->id, $recipientId)) {
            return response()->json(['error' => 'Not friends'], 403);
        }

        $chat = $this->getOrCreateChatBetween($authUser->id, $recipientId);
        $message = app(CreateMessage::class)->create($chat, $authUser->id, $request->body);

        $streak = $chat->streak ? [
            'active' => $chat->streak->active,
            'days' => $chat->streak->days,
        ] : null;

        return response()->json([
            'message' => $this->formatMessage($message),
            'chat' => ['id' => $chat->id],
            'streak' => $streak,
        ]);
    }

    private function areFriends(int $userId1, int $userId2): bool
    {
        return \App\Models\Friendship::where('status', 'accepted')
            ->where(function ($q) use ($userId1, $userId2) {
                $q->where('sender_id', $userId1)->where('receiver_id', $userId2)
                    ->orWhere('sender_id', $userId2)->where('receiver_id', $userId1);
            })
            ->exists();
    }

    private function findChatBetween(int $userId1, int $userId2): ?Chat
    {
        return Chat::whereHas('users', fn ($q) => $q->where('user_id', $userId1))
            ->whereHas('users', fn ($q) => $q->where('user_id', $userId2))
            ->first();
    }

    private function getOrCreateChatBetween(int $userId1, int $userId2): Chat
    {
        $chat = $this->findChatBetween($userId1, $userId2);
        if ($chat) {
            return $chat;
        }
        $chat = Chat::create();
        $chat->users()->attach([$userId1, $userId2]);
        return $chat;
    }

    private function formatMessage(Message $message): array
    {
        $payload = [
            'id' => $message->id,
            'chat_id' => $message->chat_id,
            'user_id' => $message->user_id,
            'body' => $message->body,
            'created_at' => $message->created_at->toIso8601String(),
            'user' => [
                'id' => $message->user->id,
                'name' => $message->user->name,
                'avatar_url' => $message->user->avatar_url ?? null,
            ],
        ];
        if ($message->relationLoaded('shareable') && $message->shareable) {
            $payload['shareable'] = $this->formatShareable($message->shareable);
        }
        return $payload;
    }

    private function formatShareable(Model $shareable): array
    {
        $out = [
            'type' => class_basename($shareable),
            'id' => $shareable->getKey(),
        ];
        if ($shareable instanceof \App\Models\Snippet) {
            $shareable->loadMissing('track:id,title,preview');
            $out['snippet'] = [
                'id' => $shareable->id,
                'track' => $shareable->track ? [
                    'id' => $shareable->track->id,
                    'title' => $shareable->track->title,
                    'preview_url' => $shareable->track->preview_url ?? null,
                ] : null,
            ];
        }
        return $out;
    }
}
