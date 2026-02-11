<?php

namespace App\Services\Home\Recommendation\InstanceCandidates;

use App\Services\Home\Recommendation\InstanceCandidates\CandidateProvider;
use Illuminate\Support\Collection;
use App\Models\Track;


class TrackCandidateProvider implements CandidateProvider {
    public function getCandidates(array $excludeIds): Collection
    {
        return Track::with('tags')
            ->whereNotIn('track_id', $excludeIds)
            ->get();
    }
}