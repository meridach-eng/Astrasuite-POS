<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagoVenta extends Model
{
    use HasFactory;

    protected $table = 'pago_ventas';

    protected $fillable = [
        'venta_id',
        'metodo_pago',
        'monto',
        'recibido',
        'cambio',
        'referencia',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'recibido' => 'decimal:2',
        'cambio' => 'decimal:2',
    ];

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }
}