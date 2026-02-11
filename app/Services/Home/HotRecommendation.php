<?php

namespace App\Services\Home;

use App\Models\UserListen;
use App\Models\Track;
use App\Services\Home\Recommendation\ParametersVectorBuilder;
use App\Services\Home\Recommendation\UserPreferenceBuilder;
use App\Services\Home\Recommendation\InstanceVectorBuilder;
use App\Services\Home\Recommendation\TagVectorBuilder;

class HotRecommendation
{
    protected int $userId;
    protected string $relation;
    protected int $limit;

    protected array $relationToModel = [
        'track' => [
            'with' => 'track.tags',
            'relation' => 'track',
            'model' => \App\Models\Track::class,
            'id_path' => 'track_id',
            'tags_path' => fn($item) => $item->track->tags,
            'parameters_path' => fn($item, $param) => $item->track->parameters[$param] ?? 0,
        ],
        'snippet' => [
            'with' => 'snippet.track.tags',
            'relation' => 'snippet.track',
            'model' => \App\Models\Track::class, // кандидатами всё равно треки
            'id_path' => 'snippet_id',
            'tags_path' => fn($item) => $item->snippet->track->tags,
            'parameters_path' => fn($item, $param) => $item->snippet->track->parameters[$param] ?? 0,
        ],
    ];

    public function __construct(int $userId, string $relation, int $limit = 10) {
        $this->userId = $userId;
        $this->relation = $relation;
        $this->limit = $limit;
    }

    public function getHotRecommendation() : array {
        $config = $this->relationToModel[$this->relation];    

        // Формируем профиль интересов юзера
        $preferenceBuilder = new UserPreferenceBuilder($this->userId);
        $userData = $preferenceBuilder->build($config['relation']);

        if (!$userData) {
            return [];
        }

        $userListens = $userData['listens'];
        $allTagIds = $userData['tag_ids'];

        // Исключение проосмотренного контента
        $modelClass = $config['model'] ?? null;

        if (!$modelClass) {
            throw new \Exception("Unknown relation: {$this->relation}");
        }

        $excludeIds = $userListens->pluck($config['id_path'])->toArray();

        $candidates = $config['model']::with('tags')
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
        );

        $userVector = array_merge($userDiscreteVector, $userContinueVector);

        foreach ($candidates as $candidate) {
            $trackDiscreteVector = InstanceVectorBuilder::build(
                $candidate,
                $allTagIds,
                ['energy', 'centroid'],
                $config['tags_path'],
                $config['parameters_path'],
                
            );
        }

        return [];
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