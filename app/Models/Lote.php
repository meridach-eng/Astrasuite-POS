<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lote extends Model
{
    use HasFactory;

    protected $table = 'lotes';

    protected $fillable = [
        'producto_id',
        'sucursal_id',
        'compra_id',
        'numero_lote',
        'fecha_vencimiento',
        'cantidad_inicial',
        'cantidad_actual',
        'costo_unitario',
        'activo',
    ];

    protected $casts = [
        'fecha_vencimiento' => 'date',
        'cantidad_inicial' => 'decimal:2',
        'cantidad_actual' => 'decimal:2',
        'costo_unitario' => 'decimal:4',
        'activo' => 'boolean',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function compra(): BelongsTo
    {
        return $this->belongsTo(Compra::class);
    }
}