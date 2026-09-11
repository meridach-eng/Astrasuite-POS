<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagoCuotaCobro extends Model
{
    use HasFactory;

    protected $table = 'pagos_cuotas_cobro';

    protected $fillable = [
        'cuota_cobro_id',
        'venta_cuota_id',
        'cliente_id',
        'user_id',
        'sesion_caja_id',
        'monto',
        'metodo_pago',
        'fecha_pago',
        'referencia',
        'notas',
    ];

    protected $casts = [
        'fecha_pago' => 'date',
        'monto' => 'decimal:2',
    ];

    public function cuota(): BelongsTo
    {
        return $this->belongsTo(CuotaCobro::class, 'cuota_cobro_id');
    }

    public function ventaCuota(): BelongsTo
    {
        return $this->belongsTo(VentaCuota::class, 'venta_cuota_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sesionCaja(): BelongsTo
    {
        return $this->belongsTo(SesionCaja::class);
    }
}