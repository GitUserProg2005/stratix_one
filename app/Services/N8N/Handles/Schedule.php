<?php

namespace App\Services\N8N\Handles;

use App\Services\N8N\BaseNode;

class Schedule extends BaseNode
{
    public function handle(): array
    {
        return $this->success($this->rawInput);
    }
}
