<?php

namespace App\Services\Home;

use App\Models\UserListen;

class RecentViewedTracks
{
    public function __construct(
        protected int $userId,
        protected int $limit = 9
    ) {
    }

    /**
     * ID треков, которые пользователь недавно слушал (последние первыми).
     *
     * @return array<int>
     */
    public function getTrackIds(): array
    {
        $ids = UserListen::query()
            ->where('user_id', $this->userId)
            ->orderByDesc('created_at')
            ->get('track_id')
            ->pluck('track_id')
            ->unique()
            ->take($this->limit)
            ->values()
            ->toArray();

        return $ids;
    }
}
