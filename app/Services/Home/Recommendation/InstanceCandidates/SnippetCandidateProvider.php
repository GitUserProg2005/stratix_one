<?php

namespace App\Services\Home\Recommendation\InstanceCandidates;

use App\Services\Home\Recommendation\InstanceCandidates\CandidateProvider;
use Illuminate\Support\Collection;
use App\Models\Snippet;


class SnippetCandidateProvider implements CandidateProvider {
    public function getCandidates(array $excludeIds): Collection
    {
        return Snippet::with('track.tags')
            ->whereNotIn('snippet_id', $excludeIds)
            ->get();
    }
}