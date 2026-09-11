<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalidaInventario extends Model
{
    use HasFactory;

    protected $table = 'salidas_inventario';

    protected $guarded = [];

    protected $casts = [
        'fecha_salida' => 'date',
        'costo_total' => 'decimal:2',
    ];

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleSalidaInventario::class, 'salida_inventario_id');
    }
}