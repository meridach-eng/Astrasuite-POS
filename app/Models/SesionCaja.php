<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class SesionCaja extends Model
{
    use HasFactory;

    protected $table = 'sesion_cajas';

    protected $fillable = [
        'caja_id',
        'user_id',
        'fecha_apertura',
        'monto_apertura',
        'fecha_cierre',
        'monto_esperado',
        'monto_real',
        'diferencia',
        'estado',
        'observaciones',
    ];

    protected $casts = [
        'fecha_apertura' => 'datetime',
        'fecha_cierre' => 'datetime',
        'monto_apertura' => 'decimal:2',
        'monto_esperado' => 'decimal:2',
        'monto_real' => 'decimal:2',
        'diferencia' => 'decimal:2',
    ];

    public function caja(): BelongsTo
    {
        return $this->belongsTo(Caja::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class);
    }

    public function pagos(): HasManyThrough
    {
        return $this->hasManyThrough(PagoVenta::class, Venta::class);
    }

    public function pagosCuotas(): HasMany
    {
        return $this->hasMany(\App\Models\PagoCuotaCobro::class, 'sesion_caja_id');
    }

    // Total en efectivo recaudado por cobro de cuotas
    public function getTotalCobroCuotasEfectivoAttribute(): float
    {
        return (float) $this->pagosCuotas()
            ->where('metodo_pago', 'EFECTIVO')
            ->sum('monto');
    }

    // Retorna desglose agrupado consolidando ventas POS + abonos de cuotas
    public function desgloseMetodosCobro(): array
    {
        // 1. Cobros de Ventas Directas POS
        $cobrosVentas = PagoVenta::whereHas('venta', function ($q) {
            $q->where('sesion_caja_id', $this->id)
              ->where('estado', '!=', 'ANULADA');
        })
        ->selectRaw('metodo_pago, SUM(monto) as total')
        ->groupBy('metodo_pago')
        ->pluck('total', 'metodo_pago')
        ->toArray();

        // 2. Cobros de Cuotas de Crédito
        $cobrosCuotas = $this->pagosCuotas()
            ->selectRaw('metodo_pago, SUM(monto) as total')
            ->groupBy('metodo_pago')
            ->pluck('total', 'metodo_pago')
            ->toArray();

        // 3. Consolidar ambos orígenes de ingresos
        $totales = $cobrosVentas;

        foreach ($cobrosCuotas as $metodo => $monto) {
            $totales[$metodo] = ($totales[$metodo] ?? 0) + (float) $monto;
        }

        return $totales;
    }

    // Calcula el dinero físico que debe haber en la gaveta (Apertura + POS Efectivo + Cuotas Efectivo)
    public function calcularEfectivoEsperado(): float
    {
        $desglose = $this->desgloseMetodosCobro();
        $cobrosEfectivo = (float) ($desglose['EFECTIVO'] ?? 0);

        return round((float) $this->monto_apertura + $cobrosEfectivo, 2);
    }
}