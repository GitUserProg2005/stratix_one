<?php

namespace App\Services\AI\Actions;

use App\Enums\MessageType;
use App\Models\Room;
use App\Services\AI\Actions\Prompts\Agent;
use App\Services\AI\Actions\Prompts\Ask;
use App\Services\AI\Actions\Prompts\Plan;

class PromptBuilder
{
    public static function build(
        MessageType $mode,
        string $userPrompt,
        string $nodesJson,
        string $edgesJson,
        string $nodeTypesCsv,
        Room $room,
        string $context = '',
        string $revisions = '',
    ): string {
        return match ($mode) {
            MessageType::ASK => Ask::build($userPrompt, $nodesJson, $edgesJson, $nodeTypesCsv, $room, $context),
            MessageType::AGENT => Agent::build($userPrompt, $nodesJson, $edgesJson, $nodeTypesCsv, $room, $context, $revisions),
            MessageType::PLAN => Plan::build($userPrompt, $nodesJson, $edgesJson, $nodeTypesCsv, $room, $context),
        };
    }
}
