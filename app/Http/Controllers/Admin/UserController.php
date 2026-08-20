<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserRequest;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function __construct(private UserService $users) {}

    public function index(Request $request): Response
    {
        $role = $request->string('role')->toString();

        return Inertia::render('admin/users/Index', [
            'users' => $this->users->paginate(
                $request->string('search')->toString() ?: null,
                $role ?: null,
            ),
            'filters' => ['search' => $request->string('search')->toString(), 'role' => $role],
            'roles' => $this->roleOptions(),
            'counts' => [
                'all' => User::count(),
                ...collect(UserRole::cases())
                    ->mapWithKeys(fn (UserRole $case): array => [
                        $case->value => User::where('role', $case->value)->count(),
                    ])
                    ->all(),
            ],
        ]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $this->users->create($request->validated());
        $this->toast('User created.');

        return back();
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $this->users->update($user, $request->validated());
        $this->toast('User updated.');

        return back();
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()?->id) {
            $this->toast('You cannot delete your own account.', 'error');

            return back();
        }

        $this->users->delete($user);
        $this->toast('User deleted.');

        return back();
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function roleOptions(): array
    {
        return array_map(
            fn (UserRole $role): array => ['value' => $role->value, 'label' => $role->label()],
            UserRole::cases(),
        );
    }
}
