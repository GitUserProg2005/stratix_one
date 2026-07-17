<?php

namespace App\Services\AI\Actions\Prompts;

interface ModePrompt
{
    public static function build(
        string $userPrompt,
        string $nodesJson,
        string $edgesJson,
        string $nodeTypesCsv,
        string $context = '',
    ): string;
}
