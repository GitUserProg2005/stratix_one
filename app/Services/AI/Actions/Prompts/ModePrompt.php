<?php

namespace App\Services\AI\Actions\Prompts;

use App\Models\Room;

interface ModePrompt
{
    public static function build(
        string $userPrompt,
        string $nodesJson,
        string $edgesJson,
        string $nodeTypesCsv,
        Room $room,
        string $context = '',
    ): string;
}
