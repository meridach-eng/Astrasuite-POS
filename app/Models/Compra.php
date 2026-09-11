<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Compra extends Model
{
    use HasFactory;

    protected $table = 'compras';

    protected $fillable = [
        'sucursal_id',
        'proveedor_id',
        'user_id',
        'numero_referencia',
        'tipo_comprobante',
        'serie_comprobante',
        'numero_comprobante',
        'fecha_compra',
        'estado_recepcion',
        'estado_pago',
        'subtotal',
        'descuento',
        'total',
        'monto_pagado',
        'saldo_pendiente',
        'observaciones',
        'motivo_anulacion',
        'anulado_at',
    ];

    protected $casts = [
        'fecha_compra' => 'date',
        'anulado_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'descuento' => 'decimal:2',
        'total' => 'decimal:2',
        'monto_pagado' => 'decimal:2',
        'saldo_pendiente' => 'decimal:2',
    ];

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleCompra::class);
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(PagoCompra::class);
    }

    public function lotes(): HasMany
    {
        return $this->hasMany(Lote::class);
    }
}