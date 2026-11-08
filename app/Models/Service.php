<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * MODELO: Service
 * Servicios de salud ofrecidos dentro de la ULS.
 */
class Service extends Model
{
    use HasFactory;

    protected $fillable = ['nombre', 'descripcion', 'activo'];

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }
}
