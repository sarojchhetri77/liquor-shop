<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PromotionRequest;
use App\Models\Promotion;
use App\Services\PromotionService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PromotionController extends Controller
{
    public function __construct(private PromotionService $promotions) {}

    public function index(): Response
    {
        return Inertia::render('admin/promotions/Index', [
            'promotions' => $this->promotions->paginate(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/promotions/Form');
    }

    public function store(PromotionRequest $request): RedirectResponse
    {
        $this->promotions->create($request->validated());
        $this->toast('Promotion created.');

        return to_route('admin.promotions.index');
    }

    public function edit(Promotion $promotion): Response
    {
        return Inertia::render('admin/promotions/Form', [
            'promotion' => $promotion,
        ]);
    }

    public function update(PromotionRequest $request, Promotion $promotion): RedirectResponse
    {
        $this->promotions->update($promotion, $request->validated());
        $this->toast('Promotion updated.');

        return to_route('admin.promotions.index');
    }

    public function destroy(Promotion $promotion): RedirectResponse
    {
        $this->promotions->delete($promotion);
        $this->toast('Promotion deleted.');

        return back();
    }
}
