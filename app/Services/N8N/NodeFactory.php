<?php

namespace App\Services\N8N;

use App\Models\Node;
use App\Enums\NodeType;


class NodeFactory {
    public function handle(Node $node, array $data) {
        match ($node->type) {
            NodeType::WEBHOOK_TRIGGER => app(WebhookTriggerRegistry::class)->register($node, $data),
            default => null 
        };
    }
}