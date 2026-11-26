<?php

namespace Tests\Feature\Console;

use Tests\TestCase;

class GenerateOpenApiDocumentationTest extends TestCase
{
    public function test_generates_openapi_specification_from_registered_routes(): void
    {
        $output = sys_get_temp_dir().DIRECTORY_SEPARATOR.'talentflow-openapi-'.uniqid().'.json';

        try {
            $this->artisan('docs:openapi', ['--output' => $output])
                ->assertSuccessful();

            $specification = json_decode((string) file_get_contents($output), true, 512, JSON_THROW_ON_ERROR);

            $this->assertSame('3.0.3', $specification['openapi']);
            $this->assertStringEndsWith('/api/v1', $specification['servers'][0]['url']);
            $this->assertArrayHasKey('/auth/login', $specification['paths']);
            $this->assertArrayHasKey('post', $specification['paths']['/auth/login']);
            $this->assertArrayNotHasKey('security', $specification['paths']['/auth/login']['post']);
            $this->assertArrayHasKey('/positions', $specification['paths']);
            $this->assertSame([['bearerAuth' => []]], $specification['paths']['/positions']['get']['security']);
            $this->assertArrayHasKey('/positions/{position}/recommendations', $specification['paths']);
            $this->assertSame([
                'name' => 'position',
                'in' => 'path',
                'required' => true,
                'description' => 'Identificador de Position.',
                'schema' => ['type' => 'integer', 'format' => 'int64'],
            ], $specification['paths']['/positions/{position}/recommendations']['get']['parameters'][0]);
            $this->assertSame(['bearerAuth'], array_keys($specification['components']['securitySchemes']));
        } finally {
            if (file_exists($output)) {
                unlink($output);
            }
        }
    }
}
