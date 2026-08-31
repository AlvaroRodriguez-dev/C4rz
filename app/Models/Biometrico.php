<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Biometrico extends Model
{
    protected $table = 'biometricos';
    protected $fillable = [
        'ip', 'agencia', 'descripcion', 'detalle', 'puerto',
        'ultima_sinc_usuarios', 'ultima_sinc_registros',   // ← nuevos
    ];

    protected $casts = [
        'ultima_sinc_usuarios'  => 'datetime',
        'ultima_sinc_registros' => 'datetime',
    ];

    public function usuarios()  { return $this->hasMany(BioUsuario::class,   'biometrico_id'); }
    public function asistencias(){ return $this->hasMany(BioAsistencia::class, 'biometrico_id'); }
}