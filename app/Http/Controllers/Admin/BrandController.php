<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BrandRequest;
use App\Services\BrandService;
use Illuminate\Http\JsonResponse;

class BrandController extends Controller
{
    public function __construct(private BrandService $brands) {}

    /**
     * Create a brand inline from the product form and hand it straight back so
     * the freshly added brand can be selected without a full page reload.
     */
    public function store(BrandRequest $request): JsonResponse
    {
        $brand = $this->brands->create($request->validated());

        return response()->json([
            'brand' => $brand->only(['id', 'name']),
        ], 201);
    }
}
