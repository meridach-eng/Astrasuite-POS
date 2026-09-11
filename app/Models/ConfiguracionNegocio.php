<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfiguracionNegocio extends Model
{
    use HasFactory;

    protected $table = 'configuracion_negocio';

    protected $fillable = [
        'nombre_comercial',
        'razon_social',
        'nit',
        'regimen_impuestos',
        'direccion',
        'telefono',
        'whatsapp',
        'email',
        'sitio_web',
        'logo',
        'fel_habilitado',
        'felplex_id',
        'felplex_api_key',
        'felplex_url',
    ];

    protected $casts = [
        'fel_habilitado' => 'boolean',
    ];

    public static function getRegistro(): self
    {
        return static::firstOrCreate([], [
            'nombre_comercial' => 'Mi Negocio POS',
            'razon_social' => 'Mi Negocio, S.A.',
            'nit' => 'CF',
            'regimen_impuestos' => 'PEQUENO_5',
        ]);
    }
}