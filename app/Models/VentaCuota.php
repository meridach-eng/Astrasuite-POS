<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VentaCuota extends Model
{
    use HasFactory;

    protected $table = 'ventas_cuotas';

    protected $fillable = [
        'sucursal_id',
        'cliente_id',
        'user_id',
        'producto_id',
        'venta_id',
        'numero_referencia',
        'fecha_inicio',
        'precio_base',
        'descuento',
        'envio_otros',
        'porcentaje_interes',
        'monto_interes',
        'total',
        'enganche',
        'saldo_financiar',
        'total_pagado',
        'saldo_pendiente',
        'numero_cuotas',
        'dias_intervalo',
        'metodo_pago_enganche',
        'estado',
        'notas',
        'motivo_anulacion',
        'anulado_at',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'anulado_at' => 'datetime',
        'precio_base' => 'decimal:2',
        'descuento' => 'decimal:2',
        'envio_otros' => 'decimal:2',
        'porcentaje_interes' => 'decimal:2',
        'monto_interes' => 'decimal:2',
        'total' => 'decimal:2',
        'enganche' => 'decimal:2',
        'saldo_financiar' => 'decimal:2',
        'total_pagado' => 'decimal:2',
        'saldo_pendiente' => 'decimal:2',
    ];

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

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleVentaCuota::class, 'venta_cuota_id');
    }

    public function cuotas(): HasMany
    {
        return $this->hasMany(CuotaCobro::class, 'venta_cuota_id')->orderBy('numero_cuota');
    }
}