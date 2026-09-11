<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sucursal extends Model
{
    use HasFactory;

    protected $table = 'sucursales';

    protected $fillable = [
        'nombre',
        'codigo_establecimiento_sat',
        'direccion',
        'telefono',
        'municipio',
        'departamento',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'sucursal_user');
    }

    public function cajas(): HasMany
    {
        return $this->hasMany(Caja::class);
    }
}