<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'carnet',
        'password',
        'role_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Relación con la tabla de roles
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    // Método para saber si es Admin
    public function isAdmin(): bool
    {
        return $this->role && $this->role->nombre === 'admin';
    }

    // Método para saber si es Doctor
    public function isDoctor(): bool
    {
        return $this->role && $this->role->nombre === 'doctor';
    }

    // Método para saber si es Cliente/Estudiante
    public function isCliente(): bool
    {
        return $this->role && $this->role->nombre === 'cliente';
    }
}
