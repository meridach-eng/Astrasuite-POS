<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CuotaCobro extends Model
{
    use HasFactory;

    protected $table = 'cuotas_cobro';

    protected $fillable = [
        'venta_cuota_id',
        'cliente_id',
        'numero_cuota',
        'monto_cuota',
        'fecha_vencimiento',
        'monto_pagado',
        'saldo_pendiente',
        'fecha_ultimo_pago',
        'estado',
    ];

    protected $casts = [
        'fecha_vencimiento' => 'date',
        'fecha_ultimo_pago' => 'date',
        'monto_cuota' => 'decimal:2',
        'monto_pagado' => 'decimal:2',
        'saldo_pendiente' => 'decimal:2',
    ];

    public function ventaCuota(): BelongsTo
    {
        return $this->belongsTo(VentaCuota::class, 'venta_cuota_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function pagos(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(PagoCuotaCobro::class, 'cuota_cobro_id');
    }
}