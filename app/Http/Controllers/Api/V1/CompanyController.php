<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => $this->scopeCompanies(Company::query())->paginate(15),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        if (! $this->isGlobal()) {
            abort(403, 'Solo un administrador global puede crear empresas.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'tax_id' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $company = Company::create($validated);

        return response()->json([
            'data' => $company,
        ], 201);
    }

    public function show(Company $company): JsonResponse
    {
        $this->ensureCompany((int) $company->id);

        return response()->json([
            'data' => $company->load('positions'),
        ]);
    }
}