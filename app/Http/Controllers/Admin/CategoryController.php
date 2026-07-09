<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function __construct(private CategoryService $categories) {}

    public function index(Request $request): Response
    {
        return Inertia::render('admin/categories/Index', [
            'categories' => $this->categories->paginate($request->string('search')->toString() ?: null),
            'filters' => ['search' => $request->string('search')->toString()],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/categories/Form');
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $this->categories->create($request->validated());
        $this->toast('Category created.');

        return to_route('admin.categories.index');
    }

    public function edit(Category $category): Response
    {
        return Inertia::render('admin/categories/Form', [
            'category' => $category,
        ]);
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $this->categories->update($category, $request->validated());
        $this->toast('Category updated.');

        return to_route('admin.categories.index');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $this->categories->delete($category);
        $this->toast('Category deleted.');

        return back();
    }
}
