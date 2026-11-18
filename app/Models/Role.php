<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 
 * Representa los 4 niveles de permisos del sistema Doctor ULS.
 */
class Role extends Model
{
    use HasFactory;

    protected $fillable = ['nombre', 'descripcion'];

    // Relación: Un rol tiene asociados múltiples usuarios
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
