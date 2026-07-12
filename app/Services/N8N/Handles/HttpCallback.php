<?php

namespace App\Services\N8N\Handles;

use App\Services\N8N\BaseNode;
use App\Services\N8N\CallbackRequest;


class HttpCallback extends BaseNode
{
    public static function dynamicInputConfigKey(): ?string
    {
        return 'payload';
    }

    public static function outputSchema(): array
    {
        return self::group(null, [
            self::field('status_code', 'integer', false),
            self::field('body', 'string', false),
        ]);
    }

    public function handle(): array
    {
        if (! $this->runId) {
            return $this->error('Run id is missing');
        }

        try {
            $result = app(CallbackRequest::class)->send(
                (string) $this->getConfig('callback_url'),
                (string) $this->getConfig('signing_secret'),
                (int) $this->workflowId,
                $this->runId,
                data_get($this->rawInput, 'data', []),
            );
        } catch (\Throwable $e) {
            return $this->error($e->getMessage());
        }

        return $this->success($result);
    }
}
