<?php

use App\Enums\UserRole;
use App\Models\User;

test('an admin can add a staff member', function () {
    $admin = User::factory()->admin()->create();

    $response = $this->actingAs($admin)->post(route('admin.staff.store'), [
        'name' => 'New Staff',
        'email' => 'new.staff@example.com',
        'role' => UserRole::Staff->value,
        'contact' => '+1 555 111 2222',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertRedirect(route('admin.staff.index'));

    $this->assertDatabaseHas('users', [
        'email' => 'new.staff@example.com',
        'role' => UserRole::Staff->value,
    ]);
});

test('staff cannot access staff management', function () {
    $staff = User::factory()->staff()->create();

    $this->actingAs($staff)->get(route('admin.staff.index'))->assertForbidden();
});

test('an admin cannot delete their own account', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->delete(route('admin.staff.destroy', $admin))->assertRedirect();

    $this->assertDatabaseHas('users', ['id' => $admin->id]);
});
