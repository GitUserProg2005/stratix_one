<?php

namespace App\Services\N8N\Handles;

use App\Services\N8N\BaseNode;


class WebhookTrigger extends BaseNode
{
    public static function outputSchema(): array {
        return [
            'request' => 'array'
        ];
    }

    public function handle(): array {
        if ($this->input) {
            return $this->success($this->input);
        }

        return $this->error('No input provided');
    }
}