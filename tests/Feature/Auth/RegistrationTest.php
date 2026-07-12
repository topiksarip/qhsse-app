<?php

beforeEach(function () {
    config()->set('auth.allow_public_registration', true);
});

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('public registration can be disabled for controlled production access', function () {
    config()->set('auth.allow_public_registration', false);

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('canRegister', false));
    $this->get('/register')->assertNotFound();
    $this->post('/register', [
        'name' => 'Unauthorized User',
        'email' => 'unauthorized@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertNotFound();

    $this->assertGuest();
});
