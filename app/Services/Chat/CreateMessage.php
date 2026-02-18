<?php

namespace App\Services\Chat;

use App\Events\MessageSent;
use App\Models\Chat;
use App\Models\Message;

class CreateMessage
{
    public function __construct(
        private UpdateStreak $updateStreak
    ) {}

    public function create(
        Chat $chat,
        int $userId,
        string $body = '',
        ?int $shareableId = null,
        ?string $shareableType = null
    ): Message {
        $message = $chat->messages()->create([
            'user_id' => $userId,
            'body' => $body,
            'shareable_id' => $shareableId,
            'shareable_type' => $shareableType,
        ]);

        $message->load(['user:id,name,avatar', 'shareable']);
        event(new MessageSent($message));

        $this->updateStreak->update($chat);

        return $message;
    }
}
