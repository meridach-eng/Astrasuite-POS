<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductoSucursal extends Model
{
    use HasFactory;

    protected $table = 'producto_sucursal';

    protected $fillable = [
        'producto_id',
        'sucursal_id',
        'stock_actual',
        'stock_minimo_sucursal',
    ];

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }
}