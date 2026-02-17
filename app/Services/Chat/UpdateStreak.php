<?php

namespace App\Services\Chat;

use App\Models\Chat;

class UpdateStreak
{
    public function update(Chat $chat): void
    {
        $streak = $chat->streak ?? $chat->streak()->create([
            'days' => 0,
            'active' => false,
        ]);

        $now = now();

        if (!$streak->last_activity_at) {
            $streak->update([
                'days' => 1,
                'last_activity_at' => $now,
            ]);
            return;
        }

        $hoursDiff = $streak->last_activity_at->diffInHours($now);

        // Если прошло >= 24 часов — сброс
        if ($hoursDiff >= 24) {
            $streak->update([
                'days' => 1,
                'last_activity_at' => $now,
                'active' => false,
            ]);
            return;
        }

        // Если новое сообщение в новый день (yesterday → today)
        if ($streak->last_activity_at->isYesterday()) {
            $streak->increment('days');
        }

        // Обновляем last_activity_at и активность (огонёк с 3 дней)
        $streak->update([
            'last_activity_at' => $now,
            'active' => $streak->fresh()->days >= 3,
        ]);
    }
}
