<?php

namespace App\Services\AI\Actions;

use App\Models\NodeType;
use App\Models\Rate;
use App\Models\User;

class CheckAccessRate
{
    public function __construct(
        public array $nodes,
        public User $user,
    ) {}

    /** true — доступ есть; false — в ответе есть нода вне тарифа. */
    public function handle(): bool
    {
        // Собираем type из ответа агента
        $types = [];
        foreach ($this->nodes as $node) {
            if (! is_array($node)) {
                continue;
            }

            $type = $node['type'] ?? null;
            if (is_string($type) && $type !== '') {
                $types[$type] = true;
            }
        }

        if ($types === []) {
            return true;
        }

        // Типы, которые вообще ограничены тарифами (есть в pivot)
        $restricted = NodeType::query()
            ->whereHas('rates')
            ->pluck('type')
            ->all();

        // Разрешённые типы текущего тарифа юзера
        $allowed = [];
        if ($this->user->rate_id) {
            $rate = Rate::query()
                ->with('nodeTypes:id,type')
                ->find($this->user->rate_id);

            $allowed = $rate?->nodeTypes->pluck('type')->all() ?? [];
        }

        // Сверяем: ограниченный type должен быть в тарифе
        foreach (array_keys($types) as $type) {
            if (! in_array($type, $restricted, true)) {
                continue;
            }

            if (! in_array($type, $allowed, true)) {
                return false;
            }
        }

        return true;
    }
}
