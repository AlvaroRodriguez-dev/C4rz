<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BioAsistencia extends Model
{
    protected $table = 'bio_asistencia';
    protected $fillable = [
        'biometrico_id', 'uid', 'user_id', 'state',
        'timestamp', 'type', 'fuente', 'archivo_origen',
    ];

    public function biometrico()
    {
        return $this->belongsTo(Biometrico::class, 'biometrico_id');
    }
}