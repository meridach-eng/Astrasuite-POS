<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Producto extends Model
{
    use HasFactory;

    protected $table = 'productos';

    protected $fillable = [
        'tipo',
        'categoria_id',
        'proveedor_id',
        'codigo_interno',
        'codigo_barras',
        'nombre',
        'descripcion',
        'imagen',
        'precio_compra',
        'margen_utilidad',
        'precio_venta',
        'precio_mayorista',
        'stock_minimo',
        'maneja_lotes',
        'activo',
    ];

    protected $casts = [
        'precio_compra' => 'decimal:4',
        'margen_utilidad' => 'decimal:2',
        'precio_venta' => 'decimal:2',
        'precio_mayorista' => 'decimal:2',
        'stock_minimo' => 'decimal:2',
        'maneja_lotes' => 'boolean',
        'activo' => 'boolean',
    ];

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(Categoria::class);
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function sucursales(): BelongsToMany
    {
        return $this->belongsToMany(Sucursal::class, 'producto_sucursal')
            ->withPivot('stock_actual', 'stock_minimo_sucursal')
            ->withTimestamps();
    }

    public function lotes(): HasMany
    {
        return $this->hasMany(Lote::class);
    }

    public function stockEnSucursal(?int $sucursalId): float
    {
        if (! $sucursalId) {
            return 0;
        }

        return (float) ($this->sucursales()->where('sucursal_id', $sucursalId)->first()?->pivot->stock_actual ?? 0);
    }
}