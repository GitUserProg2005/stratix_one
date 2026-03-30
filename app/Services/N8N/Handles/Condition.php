<?php

namespace App\Services\N8N\Handles;

class Condition
{
    public static function handleCondition($node, $prevResult)
    {
        $config = is_string($node->config)
            ? json_decode($node->config, true)
            : $node->config;

        if (! $config) {
            throw new \RuntimeException('Config not found');
        }

        $conditionTree = $config['condition'];
        $prevResult = is_string($prevResult)
            ? json_decode($prevResult, true)
            : $prevResult;

        if (! is_array($prevResult)) {
            return $conditionTree['on_false']['node_id'];
        }

        return self::evaluateCondition($conditionTree, $prevResult)
            ? $conditionTree['on_true']['node_id']
            : $conditionTree['on_false']['node_id'];
    }

    protected static function evaluateCondition($condition, $data): bool
    {
        return match ($condition['type']) {
            'group' => self::evaluateGroup($condition, $data),
            'comparison' => self::evaluateComparison($condition, $data),
            default => false,
        };
    }

    protected static function evaluateGroup($group, $data)
    {
        $results = [];

        foreach ($group['conditions'] as $condition) {
            $results[] = self::evaluateCondition($condition, $data);
        }

        return $group['op'] === 'and'
            ? ! in_array(false, $results, true)
            : in_array(true, $results, true);
    }

    protected static function evaluateComparison($condition, $data)
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
