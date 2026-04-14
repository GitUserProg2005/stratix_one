<?php

namespace App\Services\N8N\Nodes;

use App\Services\N8N\Handles\AiRequest;
use App\Services\N8N\Handles\AiAgentRequest;
use App\Services\N8N\Handles\EmailReport;
use App\Services\N8N\Handles\WebhookTrigger;

class NodeRegistry {
    public function all(): array {
        return [
            'webhook_trigger' => WebhookTrigger::class,
            'ai_request' => AiRequest::class,
            'ai_agent_request' => AiAgentRequest::class,
            'email_report' => EmailReport::class,
        ];
    }

    public function schemas(): array {
        $result = [];

        foreach ($this->all() as $type => $class) {
            $result[$type] = [
                'inputSchema' => $class::inputSchema(),
                'outputSchema' => $class::outputSchema(),
            ];
        }

        return $result;
    }
}
