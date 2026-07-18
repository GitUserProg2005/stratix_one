<?php

namespace App\Services\AI\Actions\Prompts;

final class Judge
{
    public static function build(
        string $userPrompt,
        array $builtWorkflowData,
        string $nodesJson = '[]',
        string $edgesJson = '[]',
    ): string {
        $workflowJson = json_encode($builtWorkflowData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        return <<<PROMPT
Ты судья корректности workflow (движок в духе n8n).

Тебе даны:
- ЗАПРОС_ПОЛЬЗОВАТЕЛЯ
- ТЕКУЩИЕ_УЗЛЫ / ТЕКУЩИЕ_СВЯЗИ (реальные id из БД)
- РЕЗУЛЬТАТ_АГЕНТА

Проверь, соответствует ли результат запросу: action_type, типы/названия узлов, какие связи затронуты.

НЕ проверяй config и transform.
НЕ предлагай правки ради красоты JSON.

ОСОБО ДЛЯ delete:
- edge_ids должны быть id рёбер из ТЕКУЩИЕ_СВЯЗИ (поле id), НЕ source_node_id/target_node_id и НЕ id узлов.
- Если пользователь просит удалить Н связей — в edge_ids должно быть ровно Н существующих id (или больше, если явно просили пачку).
- Если агент указал несуществующий id или пропустил часть связей — is_correct=false.
- В revisions ОБЯЗАТЕЛЬНО пиши конкретные числа id из ТЕКУЩИЕ_СВЯЗИ, например:
  1. В edge_ids укажи [90, 91] — связь webhook→mistral (id 90) и mistral→callback (id 91).

ОСОБО ДЛЯ добавления ноды:
- Если в запросе есть «от/после/к» существующей ноде — в результате ОБЯЗАН быть edge; иначе is_correct=false.
- Если агент сделал два исходящих ребра от одного source — is_correct=false; в revisions укажи вставить ноду в цепочку (X→new→Y), а не ветвить.
- Если answer на английском при русском запросе — is_correct=false; попроси answer на русском (title нод могут быть на EN).

Верни строго один JSON без markdown:

Если всё корректно:
{
  "is_correct": true,
  "revisions": ""
}

Если есть ошибки:
{
  "is_correct": false,
  "revisions": "1. ...\\n2. ..."
}

Правила для revisions:
- Нумерованный список (1. 2. 3.), по-русски, императивно.
- Всегда указывай конкретные id из ТЕКУЩИЕ_УЗЛЫ / ТЕКУЩИЕ_СВЯЗИ.

ЗАПРОС_ПОЛЬЗОВАТЕЛЯ:
{$userPrompt}

ТЕКУЩИЕ_УЗЛЫ (JSON):
{$nodesJson}

ТЕКУЩИЕ_СВЯЗИ (JSON):
{$edgesJson}

РЕЗУЛЬТАТ_АГЕНТА (JSON):
{$workflowJson}
PROMPT;
    }
}
