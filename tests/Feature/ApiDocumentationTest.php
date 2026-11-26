<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiDocumentationTest extends TestCase
{
    public function test_serves_the_generated_openapi_specification(): void
    {
        $response = $this->get('/api-docs.json')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/json');

        $file = $response->baseResponse->getFile();

        $this->assertSame(base_path('docs/openapi.json'), $file->getPathname());

        $specification = json_decode((string) file_get_contents($file->getPathname()), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('3.0.3', $specification['openapi']);
        $this->assertSame('TalentFlow API', $specification['info']['title']);
    }

    public function test_serves_the_interactive_documentation_page(): void
    {
        $this->withoutVite();

        $this->get('/api-docs')
            ->assertOk()
            ->assertSee('id="redoc"', false);
    }
}
