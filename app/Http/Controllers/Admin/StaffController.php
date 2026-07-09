<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StaffRequest;
use App\Models\User;
use App\Services\StaffService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StaffController extends Controller
{
    public function __construct(private StaffService $staff) {}

    public function index(Request $request): Response
    {
        return Inertia::render('admin/staff/Index', [
            'staff' => $this->staff->paginate($request->string('search')->toString() ?: null),
            'filters' => ['search' => $request->string('search')->toString()],
            'roles' => $this->roleOptions(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/staff/Form', [
            'roles' => $this->roleOptions(),
        ]);
    }

    public function store(StaffRequest $request): RedirectResponse
    {
        $this->staff->create($request->validated());
        $this->toast('Staff member added.');

        return to_route('admin.staff.index');
    }

    public function edit(User $staff): Response
    {
        return Inertia::render('admin/staff/Form', [
            'staff' => $staff->only(['id', 'name', 'email', 'role', 'contact']),
            'roles' => $this->roleOptions(),
        ]);
    }

    public function update(StaffRequest $request, User $staff): RedirectResponse
    {
        $this->staff->update($staff, $request->validated());
        $this->toast('Staff member updated.');

        return to_route('admin.staff.index');
    }

    public function destroy(Request $request, User $staff): RedirectResponse
    {
        if ($staff->id === $request->user()?->id) {
            $this->toast('You cannot delete your own account.', 'error');

            return back();
        }

        $this->staff->delete($staff);
        $this->toast('Staff member removed.');

        return back();
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function roleOptions(): array
    {
        return [
            ['value' => UserRole::Staff->value, 'label' => UserRole::Staff->label()],
            ['value' => UserRole::Admin->value, 'label' => UserRole::Admin->label()],
        ];
    }
}
