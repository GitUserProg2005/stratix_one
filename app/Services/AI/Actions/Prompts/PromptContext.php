<?php

namespace App\Services\AI\Actions\Prompts;

final class PromptContext
{
    public static function block(string $context): string
    {
        $body = trim($context) !== '' ? $context : 'Не заданы.';

        return <<<BLOCK
ПРАВИЛА_АГЕНТА (контекст сессии):
Это узкие правила сессии. Применяй ИХ БУКВАЛЬНО и ТОЛЬКО к тому, о чём они говорят.
- Если контекст говорит про названия/title нод (например «на английском») — это касается ТОЛЬКО поля title у nodes.
- Контекст НЕ меняет язык поля answer и НЕ переводит весь ответ.
- Поле answer всегда на языке ЗАПРОСА_ПОЛЬЗОВАТЕЛЯ (обычно русский), даже если title нод на английском.

{$body}
BLOCK;
    }
}
