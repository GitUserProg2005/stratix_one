<?php

namespace App\Services\Home\Recommendation;

use App\Models\Track;
use App\Models\Snippet;
use App\Models\UserListen;
use Illuminate\Support\Facades\Log;

/**
 * Data preparation layer 
 */
class UserPreferenceBuilder {
    protected int $userId;

    public function __construct(int $userId) {
        $this->userId = $userId;
        Log::info("UserPreferenceBuilder: initialized", ['user_id' => $userId]);
    }

    public function build(string $relation) {
        Log::info("UserPreferenceBuilder: build started", ['relation' => $relation]);

        // relation: 'track' или 'snippet.track'
        $topRelationId = $this->getTopRelationId($relation);
        Log::info("UserPreferenceBuilder: top relation ID", ['top_relation_id' => $topRelationId]);

        $withRelations = explode('.', $relation);
        Log::info("UserPreferenceBuilder: with relations", ['with_relations' => $withRelations]);

        $userListens = UserListen::with($withRelations)
            ->where('user_id', $this->userId)
            ->whereNotNull($topRelationId)
            ->get();

        Log::info("UserPreferenceBuilder: user listens fetched", ['count' => $userListens->count()]);

        if ($userListens->isEmpty()) {
            Log::info("UserPreferenceBuilder: no listens found, returning null");
            return null;
        }

        // собираем все теги
        $allTagIds = $userListens
            ->flatMap(fn($listen) => $this->getTagsByRelation($listen, $relation))
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        Log::info("UserPreferenceBuilder: all tag IDs collected", ['tag_ids' => $allTagIds]);

        // вычисляем процент прослушивания
        foreach ($userListens as $listen) {
            $duration = data_get($listen, $relation . '_duration');
            $listen->procent_listen = min(
                $listen->listen_time / max($duration, 1),
                1
            );
            Log::info("UserPreferenceBuilder: listen procent calculated", [
                'listen_id' => $listen->id ?? null,
                'procent_listen' => $listen->procent_listen
            ]);
        }

        Log::info("UserPreferenceBuilder: build finished");

        return [
            'tag_ids' => $allTagIds,
            'listens' => $userListens
        ];
    }

    protected function getTopRelationId(string $relation): string {
        $topId = explode('.', $relation)[0] . '_id';
        Log::info("UserPreferenceBuilder: getTopRelationId", ['relation' => $relation, 'top_id' => $topId]);
        return $topId;
    }

    protected function getTagsByRelation($listen, string $relation) {
        $parts = explode('.', $relation); // ['snippet','track'] или ['track']
        $obj = $listen;

        foreach ($parts as $part) {
            $obj = $obj->{$part} ?? null;
            if (!$obj) {
                Log::info("UserPreferenceBuilder: relation part not found", ['part' => $part]);
                return collect();
            }
        }

        // теперь $obj = Track, у него есть tags
        $tags = $obj->tags->pluck('id');
        Log::info("UserPreferenceBuilder: tags fetched for relation", ['relation' => $relation, 'tags' => $tags->toArray()]);
        return $tags;
    }
}
