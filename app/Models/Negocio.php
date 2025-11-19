<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Negocio extends Model
{
    protected $fillable = ['nombre', 'direccion', 'telefono', 'horario', 'agente_id'];

    public function turnos() {
        return $this->hasMany(Turno::class);
    }

    public function agente() {
        return $this->belongsTo(User::class, 'agente_id');
    }
}
