<?php

namespace Tests\Feature\Console;

use App\Enums\UserRole;
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
            $this->assertSame(37, $this->countOperations($specification));
            $this->assertArrayHasKey('/auth/login', $specification['paths']);
            $this->assertArrayHasKey('post', $specification['paths']['/auth/login']);
            $this->assertArrayNotHasKey('security', $specification['paths']['/auth/login']['post']);
            $this->assertSame(
                ['$ref' => '#/components/schemas/LoginRequestSchema'],
                $specification['paths']['/auth/login']['post']['requestBody']['content']['application/json']['schema']
            );
            $this->assertSame(
                ['$ref' => '#/components/schemas/LoginResponseSchema'],
                $specification['paths']['/auth/login']['post']['responses']['200']['content']['application/json']['schema']
            );
            $this->assertArrayHasKey('/positions', $specification['paths']);
            $this->assertSame([['bearerAuth' => []]], $specification['paths']['/positions']['get']['security']);
            $this->assertSame(
                ['$ref' => '#/components/schemas/PaginatedPositionSchema'],
                $specification['paths']['/positions']['get']['responses']['200']['content']['application/json']['schema']
            );
            $this->assertSame('company_id', $specification['paths']['/positions']['get']['parameters'][0]['name']);
            $this->assertSame('query', $specification['paths']['/positions']['get']['parameters'][0]['in']);
            $this->assertSame(
                ['$ref' => '#/components/schemas/DataPositionSchema'],
                $specification['paths']['/positions']['post']['responses']['201']['content']['application/json']['schema']
            );
            $this->assertArrayHasKey('404', $specification['paths']['/positions/{position}']['get']['responses']);
            $this->assertArrayHasKey('403', $specification['paths']['/companies']['post']['responses']);
            $this->assertArrayHasKey('403', $specification['paths']['/skills']['post']['responses']);
            $this->assertArrayHasKey('403', $specification['paths']['/applications/{application}/stage']['patch']['responses']);
            $this->assertSame(
                UserRole::values(),
                $specification['components']['schemas']['UserResourceSchema']['properties']['role']['enum']
            );
            $this->assertArrayHasKey('/positions/{position}/recommendations', $specification['paths']);
            $this->assertArrayHasKey('/notifications', $specification['paths']);
            $this->assertArrayHasKey('/notifications/read-all', $specification['paths']);
            $this->assertSame('string', $specification['paths']['/notifications/{notification}/read']['patch']['parameters'][0]['schema']['type']);
            $this->assertSame('uuid', $specification['paths']['/notifications/{notification}/read']['patch']['parameters'][0]['schema']['format']);
            $this->assertSame([
                'name' => 'position',
                'in' => 'path',
                'required' => true,
                'description' => 'Identificador de Position.',
                'schema' => ['type' => 'integer', 'format' => 'int64'],
            ], $specification['paths']['/positions/{position}/recommendations']['get']['parameters'][0]);
            $this->assertSame(
                ['$ref' => '#/components/schemas/RecommendationsResponseSchema'],
                $specification['paths']['/positions/{position}/recommendations']['get']['responses']['200']['content']['application/json']['schema']
            );
            $this->assertSame(
                ['$ref' => '#/components/schemas/PositionResourceSchema'],
                $specification['components']['schemas']['PaginatedPositionSchema']['properties']['data']['properties']['data']['items']
            );
            $this->assertSame(['bearerAuth'], array_keys($specification['components']['securitySchemes']));

            foreach ($specification['paths'] as $operations) {
                foreach ($operations as $operation) {
                    $statuses = array_keys($operation['responses']);
                    $successStatus = $statuses[0];

                    $this->assertArrayHasKey('$ref', $operation['responses'][$successStatus]['content']['application/json']['schema']);
                }
            }
        } finally {
            if (file_exists($output)) {
                unlink($output);
            }
        }
    }

    private function countOperations(array $specification): int
    {
        return array_sum(array_map(
            fn (array $operations): int => count($operations),
            $specification['paths']
        ));
    }
}
