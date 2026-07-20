<?php

namespace App\Services\N8N\Handles;

use App\Services\N8N\BaseNode;


class TaskTrigger extends BaseNode
{
    // Триггер не принимает вход от предыдущих нод
    public static function inputSchema(): array
    {
        return self::group(null, [
            self::field('status', 'string', true),
            self::field('reason', 'string', false),

            self::group('task', [
                self::field('id', 'integer', true),
                self::field('title', 'string', true),
                self::field('difficulty', 'string', false),
                self::field('due_at', 'string', false),
            ]),
        ]);
    }

    public static function outputSchema(): array
    {
        return self::group(null, [
            self::field('status', 'string', true),
            self::field('reason', 'string', false),

            self::group('task', [
                self::field('id', 'integer', true),
                self::field('title', 'string', true),
                self::field('difficulty', 'string', false),
                self::field('due_at', 'string', false),
            ]),
        ]);
    }

    public function handle(): array
    {
        if (! $this->rawInput) {
            return $this->error('No input provided');
        }

        $payload = [
            'status' => $this->input('status'),
            'reason' => $this->input('reason'),

            'task' => [
                'id' => $this->input('task.id'),
                'title' => $this->input('task.title'),
                'difficulty' => $this->input('task.difficulty'),
                'due_at' => $this->input('task.due_at'),
            ],
        ];

        return $this->success($payload);
    }
}
