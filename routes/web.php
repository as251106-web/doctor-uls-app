<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\MedicalRecordController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\UserController;
use App\Models\Service;
use Illuminate\Support\Facades\Route;

/**
 * RUTAS DEL SISTEMA DOCTOR ULS
 * Proyecto: Licenciatura en Ciencias de la Computación (ULS - Ciclo II 2026)
 */

// --------------------------------------------------------------------------
// 1. RUTA PÚBLICA / VISITANTE
// --------------------------------------------------------------------------
Route::get('/', function () {
    $user = auth()->user();

    if ($user !== null) {
        return app(DashboardController::class)->index();
    }

    $services = Service::where('activo', true)->get();

    return view('welcome', compact('services'));
})->name('home');

// --------------------------------------------------------------------------
// 2. RUTAS AUTENTICADAS (Cliente, Doctor, Admin)
// --------------------------------------------------------------------------
Route::middleware(['auth'])->group(function () {

    // Perfil y datos de la cuenta autenticada.
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Módulo de citas médicas
    Route::get('/appointments', [AppointmentController::class, 'index'])->name('appointments.index');
    Route::get('/appointments/create', [AppointmentController::class, 'create'])->name('appointments.create');
    Route::post('/appointments', [AppointmentController::class, 'store'])->name('appointments.store');
    Route::post('/appointments/{appointment}/claim', [AppointmentController::class, 'claim'])->name('appointments.claim');
    Route::patch('/appointments/{appointment}/reschedule', [AppointmentController::class, 'reschedule'])->name('appointments.reschedule');
    Route::patch('/appointments/{appointment}/status', [AppointmentController::class, 'updateStatus'])->name('appointments.updateStatus');
    Route::patch('/appointments/{appointment}/cancel', [AppointmentController::class, 'cancel'])->name('appointments.cancel');
    Route::get('/medical-history', [MedicalRecordController::class, 'index'])->name('medical-history.index');
    Route::get('/appointments/{appointment}/medical-record', [MedicalRecordController::class, 'show'])->name('medical-records.show');
    Route::patch('/appointments/{appointment}/medical-record', [MedicalRecordController::class, 'update'])->name('medical-records.update');

    // Módulo de Servicios Médicos
    Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
    Route::post('/services', [ServiceController::class, 'store'])->name('services.store');
    Route::patch('/services/{service}/toggle', [ServiceController::class, 'toggle'])->name('services.toggle');

    // Módulo de Gestión de Usuarios y Roles (Exclusivo Administrador)
    Route::get('/users', [UserController::class, 'index'])->name('users.index');

    // Listado de doctores; los cambios de rol se realizan desde Usuarios.
    Route::get('/doctors', [DoctorController::class, 'index'])->name('doctors.index');
});

// Rutas de autenticación de Breeze (login, register, logout)
require __DIR__.'/auth.php';
