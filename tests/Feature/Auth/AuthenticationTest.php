<?php

use App\Models\Role;
use App\Models\User;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'login' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('home', absolute: false));
});

test('administrators land on the system summary after login even when another page was intended', function () {
    $adminRole = Role::where('nombre', 'admin')->firstOrFail();
    $admin = User::factory()->create(['role_id' => $adminRole->id]);

    $this->get('/appointments')->assertRedirect(route('login'));

    $response = $this->post('/login', [
        'login' => $admin->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('home', absolute: false));
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Estado del sistema')
        ->assertDontSee('Inicio');
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'login' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});
