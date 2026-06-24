<?php

namespace App\Services\N8N;

use App\Services\FileStorageService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;


class IncomingDataNormalizer
{
    public function __construct(
        protected FileStorageService $fileStorage,
    ) {}

    public function normalize(?array $schema, array $raw): array
    {
        if (! $schema || ! isset($schema['type'])) {
            return $raw;
        }

        return $this->normalizeNode($schema, $raw);
    }

    public function normalizeInput(?array $schema, mixed $rawInput): array
    {
        return $this->normalize($schema, $this->extractPayload($rawInput));
    }

    /**
     * Нормализация HTTP-запроса до snapshot в очередь.
     * UploadedFile нельзя сериализовать в cache — файлы уходят в storage здесь.
     */
    public function normalizeRequest(Request $request, ?array $schema): array
    {
        $payload = $this->buildPayload($request);

        if (! $schema || ! isset($schema['type'])) {
            return $this->withoutUploadedFiles($payload);
        }

        return $this->normalize($schema, $payload);
    }

    protected function buildPayload(Request $request): array
    {
        $payload = $request->input();
        $this->mergeFiles($payload, $request->allFiles());

        return $payload;
    }

    protected function mergeFiles(array &$payload, array $files, string $prefix = ''): void
    {
        foreach ($files as $key => $value) {
            if ($value instanceof UploadedFile) {
                $path = $prefix !== '' ? "$prefix.$key" : $key;
                data_set($payload, $path, $value);

                continue;
            }

            if (is_array($value)) {
                $path = $prefix !== '' ? "$prefix.$key" : $key;
                $this->mergeFiles($payload, $value, $path);
            }
        }
    }

    protected function withoutUploadedFiles(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            if ($value instanceof UploadedFile) {
                continue;
            }

            $result[$key] = is_array($value)
                ? $this->withoutUploadedFiles($value)
                : $value;
        }

        return $result;
    }

    protected function extractPayload(mixed $rawInput): array
    {
        if (! is_array($rawInput)) {
            return [];
        }

        if (
            array_key_exists('data', $rawInput)
            && is_array($rawInput['data'])
            && array_key_exists('meta', $rawInput)
        ) {
            return $rawInput['data'];
        }

        return $rawInput;
    }

    protected function normalizeNode(array $schema, array $raw, string $prefix = ''): array
    {
        $type = $schema['type'];

        if ($type === 'field') {
            return $this->normalizeField($schema, $raw, $prefix);
        }

        if ($type === 'group') {
            return $this->normalizeGroup($schema, $raw, $prefix);
        }

        if ($type === 'array') {
            return $this->normalizeArray($schema, $raw, $prefix);
        }

        return [];
    }

    protected function normalizeField(array $schema, array $raw, string $prefix): array
    {
        $key = $schema['key'] ?? $schema['name'] ?? null;

        if ($key === null || $key === '') {
            return [];
        }

        $path = $prefix !== '' ? "$prefix.$key" : $key;
        $value = data_get($raw, $key);
        $required = $schema['required'] ?? true;

        if ($this->isFileField($schema)) {
            $storedPath = $this->storeFile($value);

            if ($storedPath === null) {
                if ($required) {
                    throw new \RuntimeException("Input $path is required");
                }

                return [$key => null];
            }

            return [$key => $storedPath];
        }

        if ($value === null && $required) {
            throw new \RuntimeException("Input $path is required");
        }

        return [$key => $value];
    }

    protected function normalizeGroup(array $schema, array $raw, string $prefix): array
    {
        $name = $schema['name'] ?? null;
        $groupRaw = $name !== null && $name !== ''
            ? data_get($raw, $name, [])
            : $raw;

        if (! is_array($groupRaw)) {
            $groupRaw = [];
        }

        $groupPath = $prefix !== '' && $name
            ? "$prefix.$name"
            : ($name ?? $prefix);

        $inner = [];

        foreach ($schema['fields'] ?? [] as $field) {
            $inner = array_merge($inner, $this->normalizeNode($field, $groupRaw, $groupPath));
        }

        if ($name !== null && $name !== '') {
            return [$name => $inner];
        }

        return $inner;
    }

    protected function normalizeArray(array $schema, array $raw, string $prefix): array
    {
        $name = $schema['name'] ?? null;

        if ($name === null || $name === '') {
            return [];
        }

        $items = data_get($raw, $name, []);

        if (! is_array($items)) {
            throw new \RuntimeException("Input $name must be array");
        }

        $arrayPath = $prefix !== '' ? "$prefix.$name" : $name;
        $normalizedItems = [];

        foreach ($items as $index => $item) {
            if (! is_array($item)) {
                throw new \RuntimeException("Input $arrayPath.$index must be array");
            }

            $normalizedItems[] = $this->normalizeNode(
                $schema['items'],
                $item,
                "$arrayPath.$index"
            );
        }

        return [$name => $normalizedItems];
    }

    protected function isFileField(array $schema): bool
    {
        if (($schema['data_type'] ?? null) === 'file') {
            return true;
        }

        return ($schema['is_file'] ?? false) === true;
    }

    protected function storeFile(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof UploadedFile) {
            return $this->fileStorage->storeUploadedFile($value);
        }

        if (! is_string($value)) {
            return null;
        }

        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $this->fileStorage->storeFromUrl($value);
        }

        if ($this->fileStorage->exists($value)) {
            return $value;
        }

        return null;
    }
}
