<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class GenerateOpenApiDocumentation extends Command
{
    protected $signature = 'docs:openapi {--output= : Ruta del archivo OpenAPI generado}';

    protected $description = 'Genera la especificación OpenAPI de la API v1 desde las rutas registradas';

    public function handle(): int
    {
        $output = (string) ($this->option('output') ?: base_path('docs/openapi.json'));

        $specification = $this->buildSpecification();

        $directory = dirname($output);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents(
            $output,
            json_encode($specification, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR).PHP_EOL
        );

        $this->info(sprintf(
            'Especificación OpenAPI generada en %s (%d operaciones).',
            $output,
            $this->countOperations($specification)
        ));

        return self::SUCCESS;
    }

    private function buildSpecification(): array
    {
        $paths = [];
        $tags = [];

        foreach (Route::getRoutes() as $route) {
            if (! str_starts_with($route->uri(), 'api/v1/')) {
                continue;
            }

            $path = '/'.Str::after($route->uri(), 'api/v1/');

            foreach ($route->methods() as $method) {
                if (in_array($method, ['HEAD', 'OPTIONS'], true)) {
                    continue;
                }

                $verb = strtolower($method);
                $operation = $this->buildOperation($route, $verb, $path);

                $paths[$path][$verb] = $operation;
                $tags = array_merge($tags, $operation['tags']);
            }
        }

        ksort($paths);

        $tags = array_values(array_unique($tags));
        sort($tags);

        return [
            'openapi' => '3.0.3',
            'info' => [
                'title' => 'TalentFlow API',
                'version' => '1.0.0',
                'description' => 'API REST v1 del ATS TalentFlow. Generada automáticamente desde las rutas registradas.',
            ],
            'servers' => [
                ['url' => rtrim((string) config('app.url'), '/').'/api/v1'],
            ],
            'tags' => array_map(fn (string $tag): array => ['name' => $tag], $tags),
            'components' => [
                'securitySchemes' => [
                    'bearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'description' => 'Token personal de Laravel Sanctum (prefijo tf_).',
                    ],
                ],
                'schemas' => $this->schemas(),
            ],
            'paths' => $paths,
        ];
    }

    private function buildOperation(RoutingRoute $route, string $method, string $path): array
    {
        $protected = in_array('auth:sanctum', $route->gatherMiddleware(), true);
        $operationConfig = $this->operationConfig($method, $path);

        $operation = [
            'tags' => [$this->tag($route)],
            'summary' => $this->summary($route),
            'description' => $this->description($route, $protected),
            'operationId' => $this->operationId($method, $path),
            'parameters' => $this->parameters($path, $operationConfig),
        ];

        if (isset($operationConfig['request'])) {
            $operation['requestBody'] = $this->requestBody($operationConfig['request']);
        }

        $operation['responses'] = $this->responses($protected, $operationConfig);

        if ($protected) {
            $operation['security'] = [['bearerAuth' => []]];
        }

        return $operation;
    }

    private function tag(RoutingRoute $route): string
    {
        $action = $route->getActionName();

        if (! str_contains($action, '@')) {
            return 'API';
        }

        [$controller] = explode('@', $action);

        return Str::before(class_basename($controller), 'Controller') ?: 'API';
    }

    private function summary(RoutingRoute $route): string
    {
        $verbs = [
            'index' => 'Listar',
            'store' => 'Crear',
            'show' => 'Obtener',
            'update' => 'Actualizar',
            'login' => 'Iniciar sesión',
            'logout' => 'Cerrar sesión',
            'me' => 'Obtener usuario autenticado',
            'stats' => 'Obtener estadísticas',
            'timeline' => 'Obtener timeline',
            'sync' => 'Sincronizar',
            'recommendations' => 'Obtener recomendaciones',
            'compare' => 'Comparar candidato con posición',
            'updateStage' => 'Cambiar etapa',
            'updateStatus' => 'Cambiar estado',
            'feedback' => 'Registrar feedback',
        ];

        $action = $route->getActionMethod();

        return sprintf('%s — %s', $verbs[$action] ?? Str::headline($action), $this->tag($route));
    }

    private function description(RoutingRoute $route, bool $protected): string
    {
        $parts = ['Operación de la API v1 de TalentFlow.'];

        if ($protected) {
            $parts[] = 'Requiere token Bearer de Sanctum.';
        }

        $throttles = collect($route->gatherMiddleware())
            ->filter(fn (mixed $middleware): bool => is_string($middleware) && str_starts_with($middleware, 'throttle:'))
            ->map(fn (mixed $middleware): string => Str::after((string) $middleware, 'throttle:'));

        if ($throttles->isNotEmpty()) {
            $parts[] = 'Throttle: '.$throttles->implode(', ').'.';
        }

        return implode(' ', $parts);
    }

    private function operationId(string $method, string $path): string
    {
        return Str::slug($method.' '.str_replace(['/', '{', '}'], [' ', '', ''], $path));
    }

    private function parameters(string $path, array $operationConfig): array
    {
        preg_match_all('/\{([^}]+)\}/', $path, $matches);

        $parameters = array_map(function (string $parameter): array {
            $optional = Str::endsWith($parameter, '?');
            $name = $optional ? Str::beforeLast($parameter, '?') : $parameter;

            return [
                'name' => $name,
                'in' => 'path',
                'required' => ! $optional,
                'description' => 'Identificador de '.Str::headline($name).'.',
                'schema' => ['type' => 'integer', 'format' => 'int64'],
            ];
        }, $matches[1]);

        if (isset($operationConfig['query'])) {
            $parameters = array_merge($parameters, $this->queryParameters($operationConfig['query']));
        }

        return $parameters;
    }

    private function queryParameters(string $schemaName): array
    {
        $schema = config('openapi.schemas.'.$schemaName, []);
        $required = $schema['required'] ?? [];
        $parameters = [];

        foreach ($schema['properties'] ?? [] as $name => $property) {
            $parameter = [
                'name' => $name,
                'in' => 'query',
                'required' => in_array($name, $required, true),
                'schema' => $property,
            ];

            if (isset($property['description'])) {
                $parameter['description'] = $property['description'];
            }

            $parameters[] = $parameter;
        }

        return $parameters;
    }

    private function requestBody(string $schemaName): array
    {
        return [
            'required' => true,
            'content' => [
                'application/json' => [
                    'schema' => $this->schemaRef($schemaName),
                ],
            ],
        ];
    }

    private function responses(bool $protected, array $operationConfig): array
    {
        $error = $this->schemaRef('ApiError');
        $status = (string) ($operationConfig['status'] ?? 200);

        $responses = [
            $status => $this->response(
                $status === '201' ? 'Recurso creado correctamente.' : 'Operación completada correctamente.',
                $this->schemaRef($operationConfig['response'] ?? 'MessageSchema')
            ),
        ];

        if ($protected) {
            $responses['401'] = $this->response('Token ausente, inválido o expirado.', $error);
        }

        if (! empty($operationConfig['forbidden'])) {
            $responses['403'] = $this->response('El usuario no tiene permisos para esta operación.', $error);
        }

        if (! empty($operationConfig['not_found'])) {
            $responses['404'] = $this->response('Recurso no encontrado o fuera del alcance de la empresa activa.', $error);
        }

        $responses['422'] = $this->response('Validación fallida o transición no permitida.', $error);
        $responses['429'] = $this->response('Límite de peticiones excedido.', $error);

        ksort($responses, SORT_NUMERIC);

        return $responses;
    }

    private function response(string $description, array $schema): array
    {
        return [
            'description' => $description,
            'headers' => [
                'X-API-Version' => [
                    'description' => 'Versión de la API.',
                    'schema' => ['type' => 'string', 'example' => '1'],
                ],
            ],
            'content' => [
                'application/json' => ['schema' => $schema],
            ],
        ];
    }

    private function schemas(): array
    {
        $schemas = array_merge(config('openapi.schemas', []), $this->envelopeSchemas());
        ksort($schemas);

        return $schemas;
    }

    private function envelopeSchemas(): array
    {
        $schemas = [];

        foreach (config('openapi.resources', []) as $name => $resourceSchema) {
            $resource = $this->schemaRef($resourceSchema);

            $schemas['Data'.$name.'Schema'] = [
                'type' => 'object',
                'required' => ['data'],
                'properties' => [
                    'data' => $resource,
                ],
            ];

            $schemas['Paginated'.$name.'Schema'] = [
                'type' => 'object',
                'required' => ['data'],
                'properties' => [
                    'data' => [
                        'type' => 'object',
                        'required' => ['data', 'links', 'meta'],
                        'properties' => [
                            'data' => [
                                'type' => 'array',
                                'items' => $resource,
                            ],
                            'links' => $this->schemaRef('PaginationLinksSchema'),
                            'meta' => $this->schemaRef('PaginationMetaSchema'),
                        ],
                    ],
                ],
            ];

            $schemas['MessageAnd'.$name.'ResponseSchema'] = [
                'type' => 'object',
                'required' => ['message', 'data'],
                'properties' => [
                    'message' => ['type' => 'string'],
                    'data' => $resource,
                ],
            ];
        }

        return $schemas;
    }

    private function schemaRef(string $schemaName): array
    {
        return ['$ref' => '#/components/schemas/'.$schemaName];
    }

    private function operationConfig(string $method, string $path): array
    {
        $operations = config('openapi.operations', []);

        return $operations[$method.' '.$path] ?? [];
    }

    private function countOperations(array $specification): int
    {
        return array_sum(array_map(
            fn (array $operations): int => count($operations),
            $specification['paths']
        ));
    }
}
