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
    \Log::info("HotRecommendation: relation configs", ['relation' => $this->relation, 'configs' => $configs]);

    if (!isset($configs[$this->relation])) {
        \Log::warning("HotRecommendation: Unknown relation", ['relation' => $this->relation]);
        throw new \InvalidArgumentException("Unknown relation: {$this->relation}");
    }

    $config = $configs[$this->relation];

    // 1️⃣ Получаем глобальные статистики треков
    $globalStats = TrackStatistics::calculate(['energy', 'centroid']);
    \Log::info("HotRecommendation: globalStats calculated", ['globalStats' => $globalStats]);

    // 2️⃣ Формируем профиль интересов пользователя
    $preferenceBuilder = new UserPreferenceBuilder($this->userId);
    $userData = $preferenceBuilder->build($config['relation']);
    \Log::info("HotRecommendation: userData built", ['userData' => $userData]);

    if (!$userData) {
        \Log::info("HotRecommendation: no userData found, returning empty array");
        return [];
    }

    $userListens = $userData['listens'];
    $allTagIds = $userData['tag_ids'];
    \Log::info("HotRecommendation: userListens and tagIds", [
        'listens_count' => $userListens->count(),
        'tag_ids' => $allTagIds
    ]);

    // 3️⃣ Выбираем исключаемые ID, которые пользователь уже слушал
    $excludeIds = $userListens->pluck($config['id_path'])->toArray();
    \Log::info("HotRecommendation: excludeIds for candidates", ['excludeIds' => $excludeIds]);

    // 4️⃣ Загружаем кандидатов для рекомендаций
    $candidates = $config['model']::with($config['with'])
        ->whereNotIn('id', $excludeIds)
        ->get();
    \Log::info("HotRecommendation: candidates loaded", [
        'count' => $candidates->count(),
        'candidate_ids' => $candidates->pluck('id')->toArray()
    ]);

    // 5️⃣ Дискретный вектор тегов для пользователя
    $userDiscreteVector = TagVectorBuilder::build(
        $userListens,
        $allTagIds,
        $config['tags_path'],
        fn ($item) => $item->procent_listen,
    );
    \Log::info("HotRecommendation: userDiscreteVector built", ['vector' => $userDiscreteVector]);

    if (!$userDiscreteVector) {
        \Log::info("HotRecommendation: userDiscreteVector is empty, returning []");
        return [];
    }

    // 6️⃣ Непрерывный вектор параметров пользователя
    $userContinueVector = ParametersVectorBuilder::build(
        $userListens,
        ['energy', 'centroid'],
        fn ($item) => $item->procent_listen,
        $config['parameters_path'],
        $globalStats
    );
    \Log::info("HotRecommendation: userContinueVector built", ['vector' => $userContinueVector]);

    $userVector = [
        ...array_values($userDiscreteVector),
        ...array_values($userContinueVector)
    ];
    
    \Log::info("HotRecommendation: combined userVector", ['userVector' => $userVector]);

    // 7️⃣ Вычисляем косинусную схожесть кандидатов с пользователем
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

        $similarity = $this->cosineSimilarity($userVector, $instanceVector);
        $scores[$candidate->id] = $similarity;

        \Log::info("HotRecommendation: candidate similarity", [
            'candidate_id' => $candidate->id,
            'similarity' => $similarity
        ]);
    }

    // 8️⃣ Сортировка и выбор топ-N
    arsort($scores);
    $topIds = array_slice(array_keys($scores), 0, $this->limit);
    \Log::info("HotRecommendation: top recommended IDs", ['topIds' => $topIds]);

    return $topIds;
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
                'with' => 'track.tags',
                'relation' => 'snippet.track', // для UserListen
                'model' => \App\Models\Snippet::class,
                'id_path' => 'snippet_id', // в UserListen есть snippet_id
                // Для UserListen: получить теги
                'tags_path' => fn($item) => $item->snippet?->track?->tags ?? collect(),
                // Для UserListen: параметры snippet через track
                'parameters_path' => fn($item, $param) => $item->snippet?->track->parameters[$param] ?? 0,
                // Для Candidate Snippet: объект Snippet сам по себе
                'instance_params_path' => fn($item, $param) => $item->track?->parameters[$param] ?? 0,
            ],
        ];
    }
}