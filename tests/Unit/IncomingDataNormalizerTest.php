<?php

namespace Tests\Unit;

use App\Services\FileStorageService;
use App\Services\N8N\IncomingDataNormalizer;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class IncomingDataNormalizerTest extends TestCase
{
    public function test_returns_raw_payload_when_schema_is_missing(): void
    {
        $normalizer = new IncomingDataNormalizer(new FileStorageService());

        $raw = ['name' => 'test'];

        $this->assertSame($raw, $normalizer->normalize(null, $raw));
    }

    public function test_normalizes_only_schema_fields(): void
    {
        $normalizer = new IncomingDataNormalizer(new FileStorageService());

        $schema = [
            'type' => 'group',
            'name' => '',
            'fields' => [
                [
                    'type' => 'field',
                    'key' => 'name',
                    'data_type' => 'string',
                    'required' => true,
                ],
            ],
        ];

        $result = $normalizer->normalize($schema, [
            'name' => 'test',
            'extra' => 'ignored',
        ]);

        $this->assertSame(['name' => 'test'], $result);
    }

    public function test_throws_when_required_field_is_missing(): void
    {
        $normalizer = new IncomingDataNormalizer(new FileStorageService());

        $schema = [
            'type' => 'field',
            'key' => 'photo',
            'data_type' => 'file',
            'required' => true,
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Input photo is required');

        $normalizer->normalize($schema, []);
    }

    public function test_normalize_request_merges_nested_multipart_files(): void
    {
        $normalizer = new IncomingDataNormalizer(new FileStorageService());

        $file = UploadedFile::fake()->create('phonk45.jpeg', 10, 'image/jpeg');

        $request = Request::create('/', 'POST', [
            'blabla' => [
                'title' => 'Картинка статуи',
            ],
        ], [], [
            'blabla' => [
                'picture' => $file,
            ],
        ]);

        $schema = [
            'type' => 'group',
            'name' => 'blabla',
            'fields' => [
                [
                    'type' => 'field',
                    'key' => 'title',
                    'data_type' => 'string',
                    'required' => true,
                ],
                [
                    'type' => 'field',
                    'key' => 'picture',
                    'data_type' => 'file',
                    'required' => false,
                ],
            ],
        ];

        $result = $normalizer->normalizeRequest($request, $schema);

        $this->assertSame('Картинка статуи', $result['blabla']['title']);
        $this->assertIsString($result['blabla']['picture']);
        $this->assertStringStartsWith('workflows/', $result['blabla']['picture']);
    }
}
