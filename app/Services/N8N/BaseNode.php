<?php

namespace App\Services\N8N;


abstract class BaseNode {
    public function __construct(
        protected $node,
        protected mixed $input
    ) {
        $this->validateInput();
    }

    abstract public function handle(): mixed;

    public static function inputSchema(): array {
        return [];
    }

    public static function outputSchema(): array {
        return [];
    }

    protected function input(string $key=null, $default=null) {
        return data_get($this->input, "data.$key", $default);
    }

    protected function validateInput(): void {
        $schema = self::inputSchema();

        if (! $schema) {
            return;
        }

        foreach ($schema as $key => $type) {
            $value = $this->input($key);

            if (! $value) {
                throw new \RuntimeException("Input $key is required");
            }

            if (! $this->validateType($value, $type)) {
                throw new \RuntimeException("Input $key must be of type $type");
            }
        }
    }

    protected function validateType($value, string $type): bool {
        return match ($type) {
            'string' => is_string($value),
            'integer' => is_int($value),
            'boolean' => is_bool($value),
            'array' => is_array($value),
            'object' => is_object($value),
            default => false,
        };
    }

    protected function hasInput(): bool {
        return is_array(data_get($this->input, 'data'));
    }

    protected function success(array|int|string|bool|null $data, array $meta=[]): array {
        $result = [
            'data' => $data,
            'meta' => $meta,
            'error' => null
        ];

        \Log::info('Node success: '.json_encode($result, JSON_UNESCAPED_UNICODE));
        
        return $result;
    }

    protected function error(string $message): array {
        return [
            'data' => [],
            'meta' => [],
            'error' => [
                'message' => $message
            ]
        ];
    }

    /**
     * Возвращает значение по ключу или сам конфиг
     * @param string $key=null ключ в конфиге ноды
     * @param default=null что вернет по дефолту
     */
    protected function getConfig(?string $key=null, $default=null) {
        $config = is_string($this->node->config) 
            ? json_decode($this->node->config, true) // проверка на валидность
            : (array) $this->node->config;

        $config = is_array($config) ? $config : [];

        if ($key) {
            return data_get($config, $key, $default);
        }

        return $config;
    }

    protected function inputToString($key = 'content'): string {
        $input = $this->input($key);

        if (is_string($input)) {
            return $input;
        }

        if (is_array($input)) {
            return json_encode(
                $input,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
            );
        }

        return (string) $input;
    }
}