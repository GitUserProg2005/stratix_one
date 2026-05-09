<?php

namespace App\Services\N8N\Handles;

use App\Services\N8N\BaseNode;


class UpdateMetric extends BaseNode
{
    public function handle(): mixed
    {
        // MVP: конфиг и фактическое обновление виджетов будет добавлено позже.
        return $this->success($this->rawInput);
    }
}

