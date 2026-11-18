<?php

namespace App\Http\Controllers;

use App\Models\User;
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

        if (! $user->isAdmin()) {
            abort(403, 'Acceso no autorizado.');
        }

        $users = User::with('role')->orderBy('id', 'desc')->get();

        return view('users.index', compact('users'));
    }
}
