<?php

namespace App\Services\N8N;

use App\Models\NodeType;

class CheckRate
{
    public function checkRate(?int $userRateId, iterable $nodes): bool
    {
        $nodeTypes = NodeType::query()
            ->with('rates:id')
            ->get()
            ->keyBy('type');

        foreach ($nodes as $node) {
            $type = is_array($node) ? ($node['type'] ?? null) : ($node->type ?? null);

            if (!$type) {
                continue;
            }

            $nodeType = $nodeTypes->get($type);

            if (!$nodeType) {
                continue;
            }

            $rateIds = $nodeType->rates->pluck('id');

            if ($rateIds->isEmpty()) {
                continue;
            }

            if (!$userRateId || !$rateIds->contains($userRateId)) {
                return false;
            }
        }

        return true;
    }
}
