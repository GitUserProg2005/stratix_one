<?php

namespace App\Services\N8N\Handles;

use App\Services\N8N\BaseNode;

use App\Models\DashboardWidget;


class UpdateMetric extends BaseNode
{
    public static function outputSchema(): array {
        return self::field('result', false);
    }

    public function handle(): mixed
    {
        $updates = $this->getConfig('updatable_metrics') ?? [];
        if (!is_array($updates) || count($updates) === 0) {
            return $this->error('No updatable_metrics provided');
        }

        // group updates by widget_id to avoid N+1
        $updatesByWidgetId = [];
        foreach ($updates as $row) {
            if (!is_array($row)) {
                continue;
            }

            $widgetId = $row['widget_id'] ?? null;
            $label = $row['label'] ?? null;
            $amount = $row['amount'] ?? null;

            if (!$widgetId || $label === null || $amount === null) {
                continue;
            }

            $updatesByWidgetId[(int) $widgetId][] = [
                'label' => (string) $label,
                'amount' => (int) $amount,
            ];
        }

        if (count($updatesByWidgetId) === 0) {
            return $this->error('No valid updatable_metrics rows');
        }

        $widgetIds = array_keys($updatesByWidgetId);
        $widgets = DashboardWidget::query()->whereIn('id', $widgetIds)->get();

        if ($widgets->isEmpty()) {
            return $this->error('Widgets not found');
        }

        foreach ($widgets as $widget) {
            $widgetId = (int) $widget->id;
            $content = is_array($widget->content) ? $widget->content : [];
            $labels = isset($content['labels']) && is_array($content['labels']) ? $content['labels'] : [];
            $values = data_get($content, 'datasets.0.values', []);
            
            if (!is_array($values)) {
                $values = [];
            }

            foreach ($updatesByWidgetId[$widgetId] ?? [] as $u) {
                $label = $u['label'];
                $amount = (int) $u['amount'];

                $labelIndex = array_search($label, $labels, true);
                if ($labelIndex === false) {
                    continue;
                }

                $values[$labelIndex] = ((int) ($values[$labelIndex] ?? 0)) + $amount;
            }

            $content['datasets'][0]['values'] = $values;
            $widget->content = $content;
            $widget->save();
        }

        return $this->success([
            'content' => 'Metrics updated'
        ]);
    }
}

