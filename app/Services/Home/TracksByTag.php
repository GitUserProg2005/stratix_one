<?php

namespace App\Services\Home;

use App\Models\Tag;

class TracksByTag
{
    public function __construct(
        protected int $tagsLimit = 10,
        protected int $tracksPerTagLimit = 10
    ) {
    }

    /**
     * Группировка треков по тегам: tag_id => [track_id, ...]
     *
     * @return array<int, array<int>>
     */
    public function getGrouped(): array
    {
        $tags = Tag::query()
            ->has('tracks')
            ->limit($this->tagsLimit)
            ->get();

        $grouped = [];
        foreach ($tags as $tag) {
            $grouped[$tag->id] = $tag->tracks()
                ->limit($this->tracksPerTagLimit)
                ->pluck('tracks.id')
                ->toArray();
        }

        return $grouped;
    }
}
