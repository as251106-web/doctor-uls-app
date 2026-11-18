<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * Listado general de usuarios (Solo Admin).
     */
    public function index()
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user->isAdmin()) {
            abort(403, 'Acceso no autorizado.');
        }

        $users = User::with('role')->orderBy('id', 'desc')->get();
        $roles = Role::all();

        return view('users.index', compact('users', 'roles'));
    }

    /**
     * Actualizar el rol de un usuario.
     */
    public function updateRole(Request $request, User $user)
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        if (!$authUser->isAdmin()) {
            abort(403, 'Acceso no autorizado.');
        }

        $request->validate([
            'role_id' => 'required|exists:roles,id',
        ]);

        $user->update(['role_id' => $request->role_id]);

        return redirect()->back()->with('success', 'El rol del usuario ha sido actualizado correctamente.');
    }
}
