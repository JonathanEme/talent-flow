<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CompanyStoreRequest;
use App\Http\Resources\Api\V1\CompanyResource;
use App\Models\Company;
use Illuminate\Http\JsonResponse;

class CompanyController extends Controller
{
    public function index(): JsonResponse
    {
        return $this->paginatedResponse(CompanyResource::collection($this->scopeCompanies(Company::query())->paginate(15)));
    }

    public function store(CompanyStoreRequest $request): JsonResponse
    {
        $company = Company::create($request->validated());

        return response()->json([
            'data' => new CompanyResource($company),
        ], 201);
    }

    public function show(Company $company): JsonResponse
    {
        $this->ensureCompany((int) $company->id);

        return response()->json([
            'data' => new CompanyResource($company->load('positions')),
        ]);
    }
}
