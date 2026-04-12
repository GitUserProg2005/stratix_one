<?php

namespace App\Services\N8N;


abstract class BaseNode {
    public function __construct(
        protected $node,
        protected mixed $input
    ) {}

    abstract public function handle(): mixed;

    public function inputSchema(): array {
        return [];
    }

    public function outputSchema(): array {
        return [];
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
}