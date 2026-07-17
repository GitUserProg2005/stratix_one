<?php

namespace App\Services\AI\Actions\Prompts;

use App\Models\Room;

final class PromptContext
{
    public static function block(string $context, Room $room): string
    {
        $body = trim($context) !== '' ? $context : 'Не заданы.';

        // Берём последние 5 сообщений комнаты (от старых к новым)
        $historyLines = $room->messages()
            ->orderByDesc('id')
            ->limit(5)
            ->pluck('text')
            ->reverse()
            ->values()
            ->filter(fn ($text) => is_string($text) && trim($text) !== '')
            ->map(fn ($text, $i) => ($i + 1).'. '.$text)
            ->implode("\n");

        if ($historyLines === '') {
            $historyLines = 'Пока нет.';
        }

        return <<<BLOCK
ПРАВИЛА_АГЕНТА (контекст сессии):
Это узкие правила сессии. Применяй ИХ БУКВАЛЬНО и ТОЛЬКО к тому, о чём они говорят.
- Если контекст говорит про названия/title нод (например «на английском») — это касается ТОЛЬКО поля title у nodes.
- Контекст НЕ меняет язык поля answer и НЕ переводит весь ответ.
- Поле answer всегда на языке ЗАПРОСА_ПОЛЬЗОВАТЕЛЯ (обычно русский), даже если title нод на английском.

{$body}

ИСТОРИЯ_ДИАЛОГА (последние сообщения сессионной комнаты, учти при ответе):
{$historyLines}
BLOCK;
    }
}
