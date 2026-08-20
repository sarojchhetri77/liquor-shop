<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BrandRequest;
use App\Models\Brand;
use App\Services\BrandService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BrandController extends Controller
{
    public function __construct(private BrandService $brands) {}

    public function index(Request $request): Response
    {
        return Inertia::render('admin/brands/Index', [
            'brands' => $this->brands->paginate($request->string('search')->toString() ?: null),
            'filters' => ['search' => $request->string('search')->toString()],
        ]);
    }

    /**
     * Create a brand. The product form adds brands inline over plain XHR and
     * needs the new record back as JSON; the brands admin page posts through
     * Inertia and just wants to land back on the list.
     */
    public function store(BrandRequest $request): JsonResponse|RedirectResponse
    {
        $brand = $this->brands->create($request->validated());

        if (! $request->header('X-Inertia')) {
            return response()->json([
                'brand' => $brand->only(['id', 'name']),
            ], 201);
        }

        $this->toast('Brand added.');

        return back();
    }

    public function update(BrandRequest $request, Brand $brand): RedirectResponse
    {
        $this->brands->update($brand, $request->validated());
        $this->toast('Brand updated.');

        return back();
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        $this->brands->delete($brand);
        $this->toast('Brand deleted.');

        return back();
    }
}
