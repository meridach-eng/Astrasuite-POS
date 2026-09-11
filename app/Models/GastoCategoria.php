<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GastoCategoria extends Model
{
    use HasFactory;

    protected $table = 'gasto_categorias';
    protected $guarded = [];

    public function gastos(): HasMany
    {
        return $this->hasMany(Gasto::class, 'gasto_categoria_id');
    }
}