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
                'schemas' => [
                    'ApiError' => [
                        'type' => 'object',
                        'properties' => [
                            'message' => ['type' => 'string'],
                            'errors' => [
                                'type' => 'object',
                                'additionalProperties' => [
                                    'type' => 'array',
                                    'items' => ['type' => 'string'],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'paths' => $paths,
        ];
    }

    private function buildOperation(RoutingRoute $route, string $method, string $path): array
    {
        $protected = in_array('auth:sanctum', $route->gatherMiddleware(), true);

        $operation = [
            'tags' => [$this->tag($route)],
            'summary' => $this->summary($route),
            'description' => $this->description($route, $protected),
            'operationId' => $this->operationId($method, $path),
            'parameters' => $this->parameters($path),
            'responses' => $this->responses($protected),
        ];

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

    private function parameters(string $path): array
    {
        preg_match_all('/\{([^}]+)\}/', $path, $matches);

        return array_map(function (string $parameter): array {
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
    }

    private function responses(bool $protected): array
    {
        $error = ['$ref' => '#/components/schemas/ApiError'];

        $responses = [
            '200' => $this->response('Operación completada correctamente.', [
                'type' => 'object',
                'additionalProperties' => true,
            ]),
            '422' => $this->response('Validación fallida o transición no permitida.', $error),
            '429' => $this->response('Límite de peticiones excedido.', $error),
        ];

        if ($protected) {
            $responses['401'] = $this->response('Token ausente, inválido o expirado.', $error);
        }

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

    private function countOperations(array $specification): int
    {
        return array_sum(array_map(
            fn (array $operations): int => count($operations),
            $specification['paths']
        ));
    }
}
