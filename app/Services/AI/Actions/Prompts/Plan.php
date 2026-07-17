<?php

namespace App\Services\AI\Actions\Prompts;

final class Plan implements ModePrompt
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
Ты составляешь ПЛАН доработок workflow (канва в духе n8n).

Режим PLAN: не симулируй вызов create/update/delete в БД — только спланируй шаги. Верни строго один JSON без markdown и без текста вне JSON.

Схема ответа (пример формы — верни свой JSON такой же структуры):
{
  "answer": "Краткий статус, например: Составляю план по реализации изменений пользователя.",
  "todo": [
    { "title": "Краткая формулировка шага", "color": "#234567" },
    { "title": "Следующий шаг", "color": "#89abcd" }
  ]
}

Про color: только строка в виде HEX из 7 символов (# и 6 шестнадцатеричных цифр), подбери контрастные цвета для разных строк.

Примечания:
- Опирайся на «ЗАПРОС», «ТЕКУЩИЕ_УЗЛЫ» и «ТЕКУЩИЕ_СВЯЗИ».
- Типы узлов в плане: {$nodeTypesCsv}.
- Перечисляй todo в порядке выполнения (сверху вниз).
- Можно добавить элементы только с title и color; других ключей не требуется.

{$contextBlock}

ТЕКУЩИЕ_УЗЛЫ (JSON):
{$nodesJson}

ТЕКУЩИЕ_СВЯЗИ (JSON):
{$edgesJson}

ЗАПРОС:
{$userPrompt}
PROMPT;
    }
}
