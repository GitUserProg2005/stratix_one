<?php

namespace App\Services\N8N\Handles;

use App\Services\N8N\BaseNode;


class WebhookTrigger extends BaseNode
{
    public static function dynamicOutputConfigKey(): ?string
    {
        return 'request';
    }

    public function handle(): array {
        if (! $this->rawInput) {
            return $this->error('No input provided');
        }

        $data = is_array($this->rawInput) ? $this->rawInput : [];

        return $this->success($data);
    }
}
