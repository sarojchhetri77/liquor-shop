<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;

class StaffService
{
    /**
     * Paginate staff and administrator accounts for the admin panel.
     *
     * @return LengthAwarePaginator<int, User>
     */
    public function paginate(?string $search = null, int $perPage = 12): LengthAwarePaginator
    {
        return User::query()
            ->whereIn('role', [UserRole::Admin->value, UserRole::Staff->value])
            ->when($search, fn ($query) => $query
                ->where(fn ($q) => $q
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Create a new staff (or admin) account.
     *
     * @param  array{name: string, email: string, password: string, role?: string|null, contact?: string|null}  $data
     */
    public function create(array $data): User
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'] ?? UserRole::Staff->value,
            'contact' => $data['contact'] ?? null,
            'email_verified_at' => now(),
        ]);
    }

    /**
     * @param  array{name: string, email: string, role?: string|null, contact?: string|null, password?: string|null}  $data
     */
    public function update(User $staff, array $data): User
    {
        $staff->name = $data['name'];
        $staff->email = $data['email'];
        $staff->role = $data['role'] ?? $staff->role->value;
        $staff->contact = $data['contact'] ?? null;

        if (! empty($data['password'])) {
            $staff->password = Hash::make($data['password']);
        }

        $staff->save();

        return $staff;
    }

    public function delete(User $staff): void
    {
        $staff->delete();
    }
}
