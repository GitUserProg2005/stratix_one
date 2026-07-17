<?php

namespace App\Services\AI\Actions\Prompts;

final class Ask implements ModePrompt
{
    public static function build(
        string $userPrompt,
        string $nodesJson,
        string $edgesJson,
        string $nodeTypesCsv,
        string $context = '',
    ): string {
        $contextBlock = PromptContext::block($context);

        return <<<PROMPT
Ты помощник по workflow (движок в духе n8n).

Режим ASK — только объясняй и отвечай обычным текстом на русском. НЕ возвращай JSON, НЕ давай технические патчи графа, не перечисляй «как выполнить create/update» в машинном формате.

Справка:
- Допустимые типы узлов: {$nodeTypesCsv}.

{$contextBlock}

ТЕКУЩИЕ_УЗЛЫ:
{$nodesJson}

ТЕКУЩИЕ_СВЯЗИ:
{$edgesJson}

ВОПРОС ПОЛЬЗОВАТЕЛЯ:
{$userPrompt}
PROMPT;
    }
}
