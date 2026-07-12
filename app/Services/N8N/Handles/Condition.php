<?php

namespace App\Services\N8N\Handles;

use App\Services\N8N\BaseNode;


class Condition extends BaseNode
{
    protected function dynamicOutputSchema(): ?array
    {
        return $this->rawInput;
    }

    public function handle(): array
    {
        if (! $this->hasInput()) {
            return $this->getConfig('condition.on_false.node_id');
        }

        \Log::info($this->rawInput['data']);

        $conditionTree = $this->getConfig('condition');
        $prevResult = is_string($this->rawInput['data'])
            ? json_decode($this->rawInput['data'], true)
            : $this->rawInput['data'];

        if (! is_array($prevResult)) {
            \Log::info('!!!');
            return $conditionTree['on_false']['node_id'];
        }

        $nextNodeId = self::evaluateCondition($conditionTree, $prevResult)
            ? $conditionTree['on_true']['node_id']
            : $conditionTree['on_false']['node_id'];

        return $this->buildResult($nextNodeId);
    }

    protected function buildResult(int $nodeId): array {
        return [
            'data' => [
                'condition_data' => [
                    'next_id' => $nodeId,
                    'saved_data' => $this->rawInput['data'] ?? []
                ]
            ]
        ];
    }

    protected static function evaluateCondition(array $condition, array $data): bool
    {
        return match ($condition['type']) {
            'group' => self::evaluateGroup($condition, $data),
            'comparison' => self::evaluateComparison($condition, $data),
            default => false,
        };
    }

    protected static function evaluateGroup(array $group, array $data)
    {
        $results = [];

        foreach ($group['conditions'] as $condition) {
            $results[] = self::evaluateCondition($condition, $data);
        }

        return $group['op'] === 'and'
            ? ! in_array(false, $results, true)
            : in_array(true, $results, true);
    }

    protected static function evaluateComparison(array $condition, array $data)
    {
        $leftValue = self::extractValue($data,
            $condition['left']['path']
        );
        $rightValue = $condition['right'];

        return match ($condition['operator']) {
            '=' => $leftValue == $rightValue,
            '!=' => $leftValue != $rightValue,
            '>' => $leftValue > $rightValue,
            '<' => $leftValue < $rightValue,
            '>=' => $leftValue >= $rightValue,
            '<=' => $leftValue <= $rightValue,
            default => false,
        };
    }

    protected static function extractValue(array $data, ?string $path)
    {
        if (! $path) {
            return null;
        }

        $parts = explode('.', $path);

        $name = array_shift($parts);

        if (($data['name'] ?? null) !== $name || ! isset($data['fields'])) {
            return null;
        }

        $fieldKey = $parts[0] ?? null;

        foreach ($data['fields'] as $field) {
            if (($field['key'] ?? null) === $fieldKey) {
                return $field['value'] ?? null;
            }
        }

        return null;
    }
}
