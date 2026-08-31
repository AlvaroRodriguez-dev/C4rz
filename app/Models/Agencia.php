<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Agencia extends Model
{
    use SoftDeletes;

    protected $fillable = ['codigo', 'descripcion', 'ciudad', 'direccion', 'url_maps'];

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($m) => $m->created_by = auth()->id());
        static::updating(fn ($m) => $m->updated_by = auth()->id());
        static::deleting(function ($m) {
            if (! $m->isForceDeleting()) {
                $m->deleted_by = auth()->id();
                $m->saveQuietly();
            }
        });
    }

    public function contactos()
    {
        return $this->hasMany(ComercialContacto::class);
    }
}
