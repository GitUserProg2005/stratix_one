<?php

namespace App\Services\Home;

use App\Models\UserListen;
use App\Models\Track;


class RecommendationTracks
{
    protected int $userId;
    protected int $limit;

    public function __construct(int $userId, int $limit = 10) {
        $this->userId = $userId;
        $this->limit = $limit;
    }

    public function getRecommendedTracks() : array {
        $userListens = UserListen::with('track.tags')
            ->where('user_id', $this->userId)
            ->get();
        
        if ($userListens->isEmpty()) {
            return [];
        }

        $allTagIds = $userListens->pluck('track.tags.*.id')
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

        $maxProcent = $userListens->max('procent_listen');

        $userVector = array_fill_keys($allTagIds, 0);

        foreach ($userListens as $listen) {
            foreach ($listen->track->tags as $tag) {
                $userVector[$tag->id] += $listen->procent_listen;
            }
        }

        foreach ($userVector as $tagId => $value) {
            $userVector[$tagId] = $maxProcent > 0 ? $value / $maxProcent : 0;
        }
        
        // Добавляем в вектор параметры энергии и центроида
        $totalWeight = $userListens->sum(fn ($listen) => $listen->procent_listen);
        
        $sumEnergy = $userListens->sum(fn ($listen) => ($listen->procent_listen * ($listen->track->parameters['energy'] ?? 0)));
        $sumCentroid = $userListens->sum(fn ($listen) => ($listen->procent_listen * ($listen->track->parameters['centroid'] ?? 0)));

        $userVector['energy'] = $totalWeight > 0 ? $sumEnergy / $totalWeight : 0;
        $userVector['centroid'] = $totalWeight > 0 ? $sumCentroid / $totalWeight : 0;

        $excludeTrackIds = $userListens->pluck('track_id')->toArray();

        $tracks = Track::with('tags')
            ->whereNotIn('id', $excludeTrackIds)
            ->get();
        
        $recommendations = [];

        foreach ($tracks as $track) {
            $trackVector = [];

            foreach ($allTagIds as $tagId) {
                $trackVector[] = $track->tags->contains('id', $tagId) ? 1 : 0;
            }

            // Добавляем в вектор параметры энергии и центроида
            $trackVector['energy'] = $track->parameters['energy'] ?? 0;
            $trackVector['centroid'] = $track->parameters['centroid'] ?? 0;

            $similarity = $this->cosineSimilarity(array_values($userVector), array_values($trackVector));

            $recommendations[] = [
                'track_id' => $track->id,
                'similarity' => $similarity,
            ];
        }

        return collect($recommendations)
            ->sortByDesc('similarity')
            ->take($this->limit)
            ->pluck('track_id')
            ->toArray();
    }

    protected function cosineSimilarity(array $a, array $b) : float {
        $dot = 0;
        $normA = 0;
        $normB = 0;

        for ($i = 0; $i < count($a); $i++) {
            $dot += $a[$i] * $b[$i];
            $normA += $a[$i]**2;
            $normB += $b[$i]**2;
        }

        if ($normA == 0 || $normB == 0) {
            return 0.0;
        }

        return $dot / (sqrt($normA) * sqrt($normB));
    }
}