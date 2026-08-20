<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            // Digits only, with an optional leading "+" for a country code.
            'contact' => ['required', 'string', 'regex:/^\+?[0-9]{7,15}$/'],
            // Customers must be at least 18 years old to register.
            'dob' => ['required', 'date', 'before_or_equal:'.now()->subYears(18)->toDateString()],
            'password' => $this->passwordRules(),
        ])->validate();

        return User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'contact' => $input['contact'],
            'dob' => $input['dob'],
            'role' => UserRole::Customer->value,
            'password' => $input['password'],
        ]);
    }
}
