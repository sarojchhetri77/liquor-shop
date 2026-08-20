<?php

use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'contact' => '+15550001234',
        'dob' => '1995-05-20',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('a contact number with letters is rejected', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'contact' => 'call-me-maybe',
        'dob' => '1995-05-20',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors('contact');
    $this->assertGuest();
});

test('a contact number with spaces or punctuation is rejected', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'contact' => '+1 555 000 1234',
        'dob' => '1995-05-20',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors('contact');
    $this->assertGuest();
});

test('a registrant under 18 is rejected', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Too Young',
        'email' => 'young@example.com',
        'contact' => '9800000000',
        'dob' => now()->subYears(17)->toDateString(),
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasErrors('dob');
    $this->assertGuest();
});

test('a registrant turning 18 today can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Just Eighteen',
        'email' => 'eighteen@example.com',
        'contact' => '9800000000',
        'dob' => now()->subYears(18)->toDateString(),
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});
