<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;

class UserService
{
    /**
     * Paginate every account for the admin panel, optionally narrowed by a
     * name/email search and by role.
     *
     * @return LengthAwarePaginator<int, User>
     */
    public function paginate(?string $search = null, ?string $role = null, int $perPage = 12): LengthAwarePaginator
    {
        return User::query()
            ->withCount('orders')
            ->when($search, fn ($query) => $query
                ->where(fn ($q) => $q
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")))
            ->when($role, fn ($query) => $query->where('role', $role))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Create an account from the admin panel. Accounts made here are treated
     * as verified, since an administrator vouched for the address.
     *
     * @param  array{name: string, email: string, password: string, role?: string|null, contact?: string|null}  $data
     */
    public function create(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'] ?? UserRole::Customer->value,
            'contact' => $data['contact'] ?? null,
        ]);

        // `email_verified_at` is not mass-assignable, so it has to be set on
        // its own. Accounts made by an administrator skip verification.
        $user->markEmailAsVerified();

        return $user;
    }

    /**
     * @param  array{name: string, email: string, role?: string|null, contact?: string|null, password?: string|null}  $data
     */
    public function update(User $user, array $data): User
    {
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->role = $data['role'] ?? $user->role->value;
        $user->contact = $data['contact'] ?? null;

        // An empty password field means "leave the current one alone".
        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        return $user;
    }

    public function delete(User $user): void
    {
        $user->delete();
    }
}
