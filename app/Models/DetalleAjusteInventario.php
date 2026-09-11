<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleAjusteInventario extends Model
{
    use HasFactory;

    protected $table = 'detalle_ajustes_inventario';

    protected $guarded = [];

    protected $casts = [
        'stock_sistema' => 'decimal:2',
        'stock_fisico' => 'decimal:2',
        'diferencia' => 'decimal:2',
        'costo_unitario' => 'decimal:4',
        'subtotal' => 'decimal:2',
    ];

    public function ajuste(): BelongsTo
    {
        return $this->belongsTo(AjusteInventario::class, 'ajuste_inventario_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function lote(): BelongsTo
    {
        return $this->belongsTo(Lote::class);
    }
}