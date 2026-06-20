<?php

namespace App\Services\N8N\Nodes;

use App\Enums\NodeStructureSchema;

use App\Enums\NodeType;


class NodeRegistry {
    public function all(): array {
        return [
            NodeType::WEBHOOK_TRIGGER->value => \App\Services\N8N\Handles\WebhookTrigger::class,
            NodeType::AI_REQUEST->value => \App\Services\N8N\Handles\AiRequest::class,
            NodeType::AI_AGENT_REQUEST->value => \App\Services\N8N\Handles\AiAgentRequest::class,
            NodeType::EMAIL_REPORT->value => \App\Services\N8N\Handles\EmailReport::class,
            NodeType::OSRM->value => \App\Services\N8N\Handles\OSRM::class,
            NodeType::COLLECT_METRICS->value => \App\Services\N8N\Handles\CollectMetrics::class,
            NodeType::UPDATE_METRIC->value => \App\Services\N8N\Handles\UpdateMetric::class,
            NodeType::CONDITION->value => \App\Services\N8N\Handles\Condition::class,
            NodeType::LOG->value => \App\Services\N8N\Handles\LogNode::class,
            NodeType::SCHEDULE->value => \App\Services\N8N\Handles\Schedule::class,
        ];
    }

    public function resolve(string $type) {
        // 
    }

    public function schemas(): array {
        $result = [];

        foreach ($this->all() as $type => $class) {
            $result[$type] = [
                'inputSchema' => $class::inputSchema(),
                'outputSchema' => $class::outputSchema(),
                'dynamic' => $class::nodeStructureSchema()  === NodeStructureSchema::DYNAMIC
            ];
        }

        if (!$result) {
            return [];
        }

        return $result;
    }
}
