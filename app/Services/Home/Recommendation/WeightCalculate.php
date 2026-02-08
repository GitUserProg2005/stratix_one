<?php

namespace App\Services\Recommendation;


class ListenWeightCalculator
{
    public function weight(UserListen $listen): float
    {
        return min(
            $listen->listen_time / max($listen->track->duration, 1),
            1
        );
    }
}
