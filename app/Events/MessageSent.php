<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Message $message
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.' . $this->message->chat_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        $payload = [
            'id' => $this->message->id,
            'chat_id' => $this->message->chat_id,
            'user_id' => $this->message->user_id,
            'body' => $this->message->body,
            'created_at' => $this->message->created_at->toIso8601String(),
            'user' => [
                'id' => $this->message->user->id,
                'name' => $this->message->user->name,
                'avatar_url' => $this->message->user->avatar_url,
            ],
        ];
        if ($this->message->relationLoaded('shareable') && $this->message->shareable) {
            $payload['shareable'] = $this->formatShareable($this->message->shareable);
        }
        return ['message' => $payload];
    }

    private function formatShareable(\Illuminate\Database\Eloquent\Model $shareable): array
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
