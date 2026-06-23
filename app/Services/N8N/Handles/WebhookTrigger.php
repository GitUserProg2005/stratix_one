<?php

namespace App\Services\N8N\Handles;

use App\Enums\NodeStructureSchema;
use App\Services\N8N\BaseNode;


class WebhookTrigger extends BaseNode
{
    public static function nodeStructureSchema(): NodeStructureSchema
    {
        return NodeStructureSchema::DYNAMIC;
    }

    protected function dynamicOutputSchema(): ?array {
        return $this->getConfig('request');
    }

    public function handle(): array {
        if (! $this->rawInput) {
            return $this->error('No input provided');
        }

        $data = is_array($this->rawInput) ? $this->rawInput : [];

        return $this->success($data);
    }
}
