<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('an admin can view the user list', function () {
    $admin = User::factory()->admin()->create();
    User::factory(3)->create();

    $this->actingAs($admin)->get(route('admin.users.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/users/Index')
            ->has('users.data', 4)
            ->has('roles', 3));
});

test('the user list can be filtered by role', function () {
    $admin = User::factory()->admin()->create();
    User::factory(2)->create();
    User::factory()->staff()->create();

    $this->actingAs($admin)->get(route('admin.users.index', ['role' => UserRole::Customer->value]))
        ->assertInertia(fn ($page) => $page->has('users.data', 2));
});

test('the user list can be searched by name or email', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->create(['name' => 'Rita Gurung', 'email' => 'rita@example.com']);
    User::factory()->create(['name' => 'Bikash Thapa', 'email' => 'bikash@example.com']);

    $this->actingAs($admin)->get(route('admin.users.index', ['search' => 'rita@']))
        ->assertInertia(fn ($page) => $page->has('users.data', 1));
});

test('an admin can create a user with a password', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('admin.users.store'), [
        'name' => 'New Customer',
        'email' => 'new@example.com',
        'role' => UserRole::Customer->value,
        'contact' => '9800000000',
        'password' => 'Str0ng-Passw0rd',
        'password_confirmation' => 'Str0ng-Passw0rd',
    ])->assertRedirect();

    $user = User::firstWhere('email', 'new@example.com');

    expect($user)->not->toBeNull()
        ->and($user->role)->toBe(UserRole::Customer)
        ->and($user->email_verified_at)->not->toBeNull()
        ->and(Hash::check('Str0ng-Passw0rd', $user->password))->toBeTrue();
});

test('creating a user requires a confirmed password', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('admin.users.store'), [
        'name' => 'Mismatch',
        'email' => 'mismatch@example.com',
        'role' => UserRole::Customer->value,
        'password' => 'Str0ng-Passw0rd',
        'password_confirmation' => 'something-else',
    ])->assertSessionHasErrors('password');
});

test('a duplicate email is rejected', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->create(['email' => 'taken@example.com']);

    $this->actingAs($admin)->post(route('admin.users.store'), [
        'name' => 'Copy',
        'email' => 'taken@example.com',
        'role' => UserRole::Customer->value,
        'password' => 'Str0ng-Passw0rd',
        'password_confirmation' => 'Str0ng-Passw0rd',
    ])->assertSessionHasErrors('email');
});

test('an admin can update a user without changing the password', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create(['name' => 'Before']);
    $original = $user->password;

    $this->actingAs($admin)->put(route('admin.users.update', $user), [
        'name' => 'After',
        'email' => $user->email,
        'role' => UserRole::Staff->value,
        'password' => '',
    ])->assertRedirect();

    $user->refresh();
    expect($user->name)->toBe('After')
        ->and($user->role)->toBe(UserRole::Staff)
        ->and($user->password)->toBe($original);
});

test('an admin can reset a user password', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $this->actingAs($admin)->put(route('admin.users.update', $user), [
        'name' => $user->name,
        'email' => $user->email,
        'role' => UserRole::Customer->value,
        'password' => 'Brand-New-P4ss',
        'password_confirmation' => 'Brand-New-P4ss',
    ])->assertRedirect();

    expect(Hash::check('Brand-New-P4ss', $user->fresh()->password))->toBeTrue();
});

test('an admin can delete a user', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $this->actingAs($admin)->delete(route('admin.users.destroy', $user))->assertRedirect();

    expect(User::find($user->id))->toBeNull();
});

test('an admin cannot delete their own account', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->delete(route('admin.users.destroy', $admin))->assertRedirect();

    expect(User::find($admin->id))->not->toBeNull();
});

test('staff cannot reach user management', function () {
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->get(route('admin.users.index'))->assertForbidden();
});

test('customers cannot reach user management', function () {
    $customer = User::factory()->create();

    $this->actingAs($customer)->get(route('admin.users.index'))->assertForbidden();
});
