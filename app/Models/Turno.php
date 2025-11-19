<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Turno extends Model
{
    protected $fillable = ['usuario_id', 'negocio_id', 'estado', 'hora_inicio', 'hora_fin'];

    public function usuario() {
        return $this->belongsTo(User::class);
    }

    public function negocio() {
        return $this->belongsTo(Negocio::class);
    }
}
