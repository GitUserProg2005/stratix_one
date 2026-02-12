<?php

namespace App\Services\Home;

use App\Models\UserListen;
use App\Models\Track;
use App\Services\Home\Recommendation\ParametersVectorBuilder;
use App\Services\Home\Recommendation\UserPreferenceBuilder;
use App\Services\Home\Recommendation\InstanceVectorBuilder;
use App\Services\Home\Recommendation\TagVectorBuilder;
use App\Services\Home\Recommendation\TrackStatistics;

class HotRecommendation
{
    protected int $userId;
    protected string $relation;
    protected int $limit;

    public function __construct(int $userId, string $relation, int $limit = 10) {
        $this->userId = $userId;
        $this->relation = $relation;
        $this->limit = $limit;
    }

    public function getHotRecommendation() : array {
        $configs = $this->relationToModel();

        if (!isset($configs[$this->relation])) {
                throw new \InvalidArgumentException("Unknown relation: {$this->relation}");
            }

        $config = $configs[$this->relation];

        $globalStats = TrackStatistics::calculate(['energy', 'centroid']);

        // Формируем профиль интересов юзера
        $preferenceBuilder = new UserPreferenceBuilder($this->userId);
        $userData = $preferenceBuilder->build($config['relation']);

        if (!$userData) {
            return [];
        }

        $userListens = $userData['listens'];
        $allTagIds = $userData['tag_ids'];

        $excludeIds = $userListens->pluck($config['id_path'])->toArray();

        $candidates = $config['model']::with($config['with'])
            ->whereNotIn('id', $excludeIds)
            ->get();

        // Формируем дискретный вектор тегов для юзера
        $userDiscreteVector = TagVectorBuilder::build(
            $userListens,
            $allTagIds,
            $config['tags_path'],
            fn ($item) => $item->procent_listen,
        );

        if (!$userDiscreteVector) {
            return [];
        }

        // Формируем непрерывный вектор параметров юзера
        $userContinueVector = ParametersVectorBuilder::build(
            $userListens,
            ['energy', 'centroid'],
            fn ($item) => $item->procent_listen,
            $config['parameters_path'],
            $globalStats
        );

        $userVector = [
            ...array_values($userDiscreteVector),
            ...array_values($userContinueVector)
        ];

        $scores = [];

        foreach ($candidates as $candidate) {
            $instanceVector = InstanceVectorBuilder::build(
                $candidate,
                $allTagIds,
                ['energy', 'centroid'],
                $config['tags_path'],
                $config['instance_params_path'],
                $globalStats
            );

            $similarity = $this->cosineSimilarity(
                $userVector,
                $instanceVector
            );

            $scores[$candidate->id] = $similarity;
        }

        arsort($scores);

        return array_slice(array_keys($scores), 0, $this->limit);
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

    protected function relationToModel(): array {
        return [
            'track' => [
                'with' => 'tags', // Track реально имеет tags
                'relation' => 'track',
                'model' => \App\Models\Track::class,
                'id_path' => 'track_id',
                'tags_path' => fn($item) => $item->track?->tags ?? collect(),
                'parameters_path' => fn($item, $param) => $item->track->parameters[$param] ?? 0,
                'instance_params_path' => fn($item, $param) => $item->parameters[$param] ?? 0,
            ],
            'snippet' => [
                'with' => 'track.tags', // Snippet реально имеет track, а track имеет tags
                'relation' => 'snippet.track',
                'model' => \App\Models\Snippet::class,
                'id_path' => 'snippet_id',
                'tags_path' => fn($item) => $item->snippet?->track?->tags ?? collect(),
                'parameters_path' => fn($item, $param) => $item->snippet?->track->parameters[$param] ?? 0,
                'instance_params_path' => fn($item, $param) => $item->track->parameters[$param] ?? 0,
            ],
        ];
    }
}