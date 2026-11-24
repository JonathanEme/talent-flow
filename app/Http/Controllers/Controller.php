<?php

namespace App\Http\Controllers;

use App\Support\TenantScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;

abstract class Controller
{
    use TenantScope;

    protected function paginatedResponse(ResourceCollection $collection): JsonResponse
    {
        return response()->json([
            'data' => $collection->response()->getData(true),
        ]);
    }
}
