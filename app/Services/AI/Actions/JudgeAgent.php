<?php

namespace App\Services\AI\Actions;

use App\Services\AI\Actions\Prompts\Judge;
use App\Services\AI\Gigachat;

class JudgeAgent
{
    public function __construct(
        protected Gigachat $gigachat,
        public string $userPrompt,
        public array $builtWorkflowData,
        public string $nodesJson = '[]',
        public string $edgesJson = '[]',
    ) {}

    public function handle(): array
    {
        // Собираем промпт судьи (с текущим графом)
        $prompt = Judge::build(
            $this->userPrompt,
            $this->builtWorkflowData,
            $this->nodesJson,
            $this->edgesJson,
        );

        // Запрашиваем вердикт у модели (строгий JSON)
        $response = $this->gigachat->sendRequest($prompt, true);

        // TEMP: сырой ответ судьи
        \Log::info('AI JUDGE raw response', ['payload' => $response]);

        if (! is_array($response)) {
            return [
                'is_correct' => false,
                'revisions' => '1. Ответ судьи невалиден. Пересобери workflow строго по запросу пользователя.',
            ];
        }

        $isCorrect = (bool) ($response['is_correct'] ?? false);
        $revisions = $response['revisions'] ?? '';

        // revisions может прийти массивом — склеиваем в нумерованный список
        if (is_array($revisions)) {
            $lines = [];
            foreach (array_values($revisions) as $i => $item) {
                $text = is_string($item) ? trim($item) : trim((string) json_encode($item, JSON_UNESCAPED_UNICODE));
                if ($text === '') {
                    continue;
                }
                $lines[] = ($i + 1).'. '.$text;
            }
            $revisions = implode("\n", $lines);
        } else {
            $revisions = trim((string) $revisions);
        }

        if ($isCorrect) {
            return [
                'is_correct' => true,
                'revisions' => '',
            ];
        }

        return [
            'is_correct' => false,
            'revisions' => $revisions !== ''
                ? $revisions
                : '1. Исправь workflow по запросу пользователя: типы, названия и связи узлов.',
        ];
    }
}
