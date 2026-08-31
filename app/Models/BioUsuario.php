<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BioUsuario extends Model
{
    protected $table = 'bio_usuarios';
    protected $fillable = ['biometrico_id','uid','user_id','name','role','password','card_no'];

    public function biometrico()
    {
        return $this->belongsTo(Biometrico::class, 'biometrico_id');
    }
}