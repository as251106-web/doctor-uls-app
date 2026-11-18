<?php

use App\Models\Role;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    $clientRole = Role::where('nombre', 'cliente')->firstOrFail();
    $doctorRole = Role::where('nombre', 'doctor')->firstOrFail();

    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'carnet' => 'TEST001',
        'password' => 'password',
        'password_confirmation' => 'password',
        'role_id' => $doctorRole->id,
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('home', absolute: false));
    $this->assertDatabaseHas('users', [
        'email' => 'test@example.com',
        'role_id' => $clientRole->id,
    ]);
});
