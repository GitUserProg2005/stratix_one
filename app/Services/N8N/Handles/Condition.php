<?php

namespace App\Services\N8N\Handles;

use App\Services\N8N\BaseNode;


class Condition extends BaseNode
{
    public function handle(): int
    {
        if (! $this->hasInput()) {
            return $this->getConfig('condition.on_false.node_id');
        }

        $conditionTree = $this->getConfig('condition');
        $prevResult = is_string($this->input)
            ? json_decode($this->input, true)
            : $this->input;

        if (! is_array($prevResult)) {
            return $conditionTree['on_false']['node_id'];
        }

        return self::evaluateCondition($conditionTree, $prevResult)
            ? $conditionTree['on_true']['node_id']
            : $conditionTree['on_false']['node_id'];
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
