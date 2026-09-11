<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagoCompra extends Model
{
    use HasFactory;

    protected $table = 'pago_compras';

    protected $fillable = [
        'compra_id',
        'metodo_pago',
        'monto',
        'referencia_pago',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
    ];

    public function compra(): BelongsTo
    {
        return $this->belongsTo(Compra::class);
    }
}