<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\UserController;
use App\Models\Service;

/**
 * RUTAS DEL SISTEMA DOCTOR ULS
 * Proyecto: Licenciatura en Ciencias de la Computación (ULS - Ciclo II 2026)
 */

// --------------------------------------------------------------------------
// 1. RUTA PÚBLICA / VISITANTE
// --------------------------------------------------------------------------
Route::get('/', function () {
    $services = Service::where('activo', true)->get();
    return view('welcome', compact('services'));
})->name('home');

// --------------------------------------------------------------------------
// 2. RUTAS AUTENTICADAS (Cliente, Doctor, Admin)
// --------------------------------------------------------------------------
Route::middleware(['auth'])->group(function () {

    // Ruta principal / Redirección de Breeze
    Route::get('/dashboard', [AppointmentController::class, 'index'])->name('dashboard');

    // Módulo de Citas Médicas
    Route::get('/appointments', [AppointmentController::class, 'index'])->name('appointments.index');
    Route::get('/appointments/create', [AppointmentController::class, 'create'])->name('appointments.create');
    Route::post('/appointments', [AppointmentController::class, 'store'])->name('appointments.store');
    Route::patch('/appointments/{appointment}/status', [AppointmentController::class, 'updateStatus'])->name('appointments.updateStatus');
    Route::patch('/appointments/{appointment}/cancel', [AppointmentController::class, 'cancel'])->name('appointments.cancel');

    // Módulo de Servicios Médicos
    Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
    Route::post('/services', [ServiceController::class, 'store'])->name('services.store');
    Route::patch('/services/{service}/toggle', [ServiceController::class, 'toggle'])->name('services.toggle');

    // Módulo de Gestión de Usuarios y Roles (Exclusivo Administrador)
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::patch('/users/{user}/role', [UserController::class, 'updateRole'])->name('users.updateRole');
});

// Rutas de autenticación de Breeze (login, register, logout)
require __DIR__.'/auth.php';
