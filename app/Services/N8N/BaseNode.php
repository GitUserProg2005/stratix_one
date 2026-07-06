<?php

namespace App\Services\N8N;

use App\Enums\NodeStructureSchema;
use App\Events\WorkflowFailed;
use App\Services\FileStorageService;


abstract class BaseNode {
    public function __construct(
        protected $workflowId,
        protected $node,
        protected mixed $rawInput,
        protected ?int $nodeId = null,
    ) {
        $this->validateInput();
    }

    /*
    * Обработчик ноды
    */
    abstract public function handle(): mixed;

    /*
    * Инпут-схема по умолчанию
    */
    public static function inputSchema(): array {
        return [];
    }

    /*
    * Input-схема в зависимости от 
    * режима работы ноды (динамический инпут)
    */
    public static function inputSchemasByMode(): array 
    {
        return [];
    }

    /*
    * Резолвит инпут-схему (ноды с mode переопределяют)
    */
    public function resolveInputSchema(): ?array
    {
        $schema = static::inputSchema();

        return $schema ?: null;
    }

    /*
    * Выход-схема по умолчанию
    */
    public static function outputSchema(): array {
        return [];
    }

    /*
    * Структура ноды (статическая или динамическая)
    */
    public static function nodeStructureSchema(): NodeStructureSchema  {
        return NodeStructureSchema::STATIC;
    }

    /*
    * Резолвит выход-схему в зависимости от структуры ноды
    */
    public function resolveOutputSchema(): ?array {
        if (static::nodeStructureSchema() === NodeStructureSchema::DYNAMIC) {
            return $this->dynamicOutputSchema();
        }

        return static::outputSchema();
    }

    protected function dynamicOutputSchema(): ?array {
        return null;
    }

    protected static function field(string $key, string $type = null, bool $is_required=true): array {
        return [
            'type' => 'field',
            'key' => $key,
            'required' => $is_required,
            'data_type' => $type
        ];
    }

    protected static function array(string $name, array $items): array
    {
        // items — схема элемента (group) или список полей
        $itemsSchema = isset($items['type'])
            ? $items
            : self::group(null, $items);

        return [
            'type' => 'array',
            'name' => $name,
            'items' => $itemsSchema,
        ];
    }

    protected static function group(?string $name, array $fields): array {
        return [
            'type' => 'group',
            'name' => $name,
            'fields' => $fields
        ];
    }

    protected function input(string $key=null, $default=null) {
        return data_get(
            $this->rawInput,
            "data.$key",
            $default
        );
    }

    protected function validateInput(): void
    {
        $schema = $this->resolveInputSchema();

        if (! $schema) {
            return;
        }

        $this->validateSchema($schema);
    }

    protected function validateSchema(array $schema, $data = null, $prefix = ''): void {
        $data ??= data_get($this->rawInput, 'data', []);

        $type = $schema['type'];

        if ($type === 'field') {
            $key = $schema['key'] ?? $schema['name'];

            $path = $prefix
                ? "$prefix.$key"
                : $key;

            $value = data_get($data, $key, null);
            $required = $schema['required'] ?? true;

            if ($value === null) {
                if ($required) {
                    $error = "Input $path is required";

                    $this->broadcastError($error);
                    // throw new \RuntimeException($error);
                }
            }

            // if (isset($schema['data_type'])) {
            //     if (! $this->validateType($value, $schema['data_type'])) {
            //         throw new \RuntimeException(
            //             "Input $path must be {$schema['data_type']}"
            //         );
            //     }
            // }

            return;
        }

        if ($type === 'group') {
            $name = $schema['name'] ?? null;

            $groupData = $name
                ? data_get($data, $name, [])
                : $data;

            $path = $prefix && $name
                ? "$prefix.$name"
                : ($name ?? $prefix);

            foreach ($schema['fields'] as $field) {
                $this->validateSchema($field, $groupData, $path);
            }

            return;
        }

        if ($type === 'array') {
            $name = $schema['name'];

            $items = data_get($data, $name);

            if (! is_array($items)) {
                throw new \RuntimeException("Input $name must be array");
                $this->broadcastError("Input $name must be array");
            }

            foreach ($items as $index => $item) {

                $path = $prefix
                    ? "$prefix.$name.$index"
                    : "$name.$index";

                $this->validateSchema(
                    $schema['items'],
                    $item,
                    $path
                );
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
            'file' => is_string($value) && $value !== '',
            default => false,
        };
    }

    protected function fileFromPath(string $path): ?string
    {
        return app(FileStorageService::class)->get($path);
    }

    protected function hasInput(): bool {
        return is_array(data_get($this->rawInput, 'data'));
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
        $this->broadcastError($message);

        return [
            'data' => [],
            'meta' => [],
            'error' => [
                'message' => $message
            ]
        ];
    }

    protected function broadcastError(string $error) {
        $currentNodeId = $this->nodeId ?? $this->node->id ?? null;

        if ($currentNodeId === null) {
            \Log::error($error);

            return;
        }

        broadcast(new WorkflowFailed(
                $this->workflowId,
                (int) $currentNodeId,
                $error,
            )
        );
    }

    protected function getConfig(?string $key=null, $default=null) {
        $config = is_string($this->node->config) 
            ? json_decode($this->node->config, true)
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