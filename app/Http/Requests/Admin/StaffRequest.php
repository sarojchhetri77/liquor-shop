<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>|string>
     */
    public function rules(): array
    {
        /** @var User|null $staff */
        $staff = $this->route('staff');
        $isUpdate = $staff !== null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($staff?->id),
            ],
            'role' => ['required', Rule::in([UserRole::Admin->value, UserRole::Staff->value])],
            'contact' => ['nullable', 'string', 'max:30'],
            'password' => [$isUpdate ? 'nullable' : 'required', 'confirmed', Password::defaults()],
        ];
    }
}
