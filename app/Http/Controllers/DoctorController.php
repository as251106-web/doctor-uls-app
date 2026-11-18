<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DoctorController extends Controller
{
    public function index(): View
    {
        $this->authorizeAdmin();

        $doctorRole = Role::where('nombre', 'doctor')->firstOrFail();
        $doctors = $doctorRole->users()->orderBy('name')->get();

        return view('doctors.index', compact('doctors'));
    }

    private function authorizeAdmin(): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        abort_unless($user?->isAdmin(), 403, 'Acceso no autorizado.');
    }
}
