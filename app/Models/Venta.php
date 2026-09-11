<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Venta extends Model
{
    use HasFactory;

    protected $table = 'ventas';

    protected $fillable = [
        'sesion_caja_id',
        'sucursal_id',
        'cliente_id',
        'user_id',
        'numero_ticket',
        'fecha_venta',
        'fecha_vencimiento',
        'tipo_venta',
        'estado',
        'estado_pago',
        'subtotal',
        'descuento',
        'impuesto',
        'total',
        'monto_pagado',
        'saldo_pendiente',
        'cambio_entregado',
        'tipo_dte',
        'fel_uuid',
        'fel_serie',
        'fel_numero',
        'notas',
        'motivo_anulacion',
        'anulado_at',
    ];

    protected $casts = [
        'fecha_venta' => 'datetime',
        'fecha_vencimiento' => 'date',
        'anulado_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'descuento' => 'decimal:2',
        'impuesto' => 'decimal:2',
        'total' => 'decimal:2',
        'monto_pagado' => 'decimal:2',
        'saldo_pendiente' => 'decimal:2',
        'cambio_entregado' => 'decimal:2',
    ];

    public function sesionCaja(): BelongsTo
    {
        return $this->belongsTo(SesionCaja::class, 'sesion_caja_id');
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleVenta::class);
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(PagoVenta::class);
    }
}