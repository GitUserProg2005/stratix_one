<?php

namespace App\Services\Home\Recommendations;

use App\Models\Track;
use App\Models\Snippet;
use App\Models\UserListen;


/**
 * Data preparation layer 
 */
class UserPreferenceBuilder {
    protected int $userId;

    public function __construct(int $userId) {
        $this->userId = $userId;
    }
    
    public function build(string $relation) {
        $userListens = UserListen::with("$relation.tags")
            ->where('user_id', $this->userId)
            ->whereNotNull("{$relation}_id")
            ->get();
        
        if ($userListens->isEmpty()) {
            return null;
        }

        $allTagIds = $userListens
            ->pluck("$relation.tags.*.id")
            ->flatten()
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        foreach ($userListens as $listen) {
            $listen->procent_listen = min(
                $listen->listen_time / max($listen->track->duration, 1),
                1
            );   
        }

        return [
            'tag_ids' => $allTagIds,
            'listens' => $userListens
        ];
    }
}