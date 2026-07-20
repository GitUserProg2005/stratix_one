<?php

namespace App\Services\N8N\Handles;

use App\Services\N8N\BaseNode;

class ToString extends BaseNode
{
    // Принимает любой вход без жёсткой схемы
    public static function inputSchema(): array
    {
        return [];
    }

    // На выходе одно строковое поле
    public static function outputSchema(): array
    {
        return self::field('result', 'string', true);
    }

    public function handle(): array
    {
        // Берём data из результата предыдущей ноды (или весь rawInput)
        $payload = data_get($this->rawInput, 'data', $this->rawInput);

        if (is_string($payload)) {
            $result = $payload;
        } else {
            $result = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }

        if ($result === false) {
            return $this->error('Не удалось сериализовать вход в строку');
        }

        return $this->success(['result' => $result]);
    }
}
