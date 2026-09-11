<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;

    protected $table = 'clientes';

    protected $fillable = [
        'tipo_documento',
        'numero_documento',
        'nombre',
        'nombre_comercial',
        'direccion',
        'telefono',
        'email',
        'imagen',
        'limite_credito',
        'dias_credito',
        'activo',
    ];

    protected $casts = [
        'limite_credito' => 'decimal:2',
        'dias_credito' => 'integer',
        'activo' => 'boolean',
    ];

    /**
     * Helper para obtener o generar el cliente Consumidor Final por defecto
     */
    public static function getConsumidorFinal(): self
    {
        return static::firstOrCreate(
            ['numero_documento' => 'CF'],
            [
                'tipo_documento' => 'NIT',
                'nombre' => 'CONSUMIDOR FINAL',
                'direccion' => 'CIUDAD',
                'activo' => true,
            ]
        );
    }
}