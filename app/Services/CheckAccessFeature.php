<?php

namespace App\Services;

use App\Enums\FeatureType;
use App\Models\Rate;

class CheckAccessFeature
{
    /**
     * @var array<string, array{message: string, rate_titles: list<string>}>
     */
    private const FEATURES = [
        FeatureType::DASHBOARD->value => [
            'message' => 'Дашборды и метрики доступны по тарифу Ignium и выше',
            'rate_titles' => ['Ignium', 'Polium', 'Hornium'],
        ],
        FeatureType::CRM->value => [
            'message' => 'CRM и воронки доступны по тарифу Polium и выше',
            'rate_titles' => ['Polium', 'Hornium'],
        ],
        FeatureType::AI_AGENT->value => [
            'message' => 'AI Agent доступен по тарифу Hornium',
            'rate_titles' => ['Hornium'],
        ],
        FeatureType::TASK_MANAGER->value => [
            'message' => 'Таск-менеджер доступен по тарифу Polium и выше',
            'rate_titles' => ['Polium', 'Hornium'],
        ],
    ];

    public function canAccess(?int $rateId, string $feature): bool
    {
        $config = self::FEATURES[$feature] ?? null;

        if (!$config || !$rateId) {
            return false;
        }

        $rateTitle = Rate::query()->whereKey($rateId)->value('title');

        return $rateTitle && in_array($rateTitle, $config['rate_titles'], true);
    }

    public function errorMessage(string $feature): string
    {
        return self::FEATURES[$feature]['message'] ?? 'У вас нет доступа к этому разделу';
    }

    /**
     * @return array{has_access: bool, access_error: string|null}
     */
    public function resolve(?int $rateId, string $feature): array
    {
        $hasAccess = $this->canAccess($rateId, $feature);

        return [
            'has_access' => $hasAccess,
            'access_error' => $hasAccess ? null : $this->errorMessage($feature),
        ];
    }
}
