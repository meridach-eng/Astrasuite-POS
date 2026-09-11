<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleSalidaInventario extends Model
{
    use HasFactory;

    protected $table = 'detalle_salidas_inventario';

    protected $guarded = [];

    protected $casts = [
        'cantidad' => 'decimal:2',
        'costo_unitario' => 'decimal:4',
        'subtotal' => 'decimal:2',
    ];

    public function salida(): BelongsTo
    {
        return $this->belongsTo(SalidaInventario::class, 'salida_inventario_id');
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