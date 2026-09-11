<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleTraslado extends Model
{
    use HasFactory;

    protected $table = 'detalle_traslados';

    protected $guarded = [];

    protected $casts = [
        'cantidad' => 'decimal:2',
    ];

    public function traslado(): BelongsTo
    {
        return $this->belongsTo(Traslado::class, 'traslado_id');
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