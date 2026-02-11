<?php

namespace App\Services\Home\Recommendation\InstanceCandidates;

use Illuminate\Support\Collection;


interface CandidateProvider {
    public function getCandidates(array $excludeIds): Collection;
}